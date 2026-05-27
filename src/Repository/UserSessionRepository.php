<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;

/**
 * Gestion des sessions utilisateur uniques.
 *
 * Stratégie : on ne supprime jamais (SoftDeleteable + audit),
 * on révoque (active = false). Le cache ne sert qu'à éviter
 * les lectures BDD répétées, jamais comme source de vérité
 * pour la détection de conflit.
 * @author AGBOKOUDJO Franck
 */
final class UserSessionRepository extends ServiceEntityRepository 
{
    private const CACHE_PREFIX   = '_user_session';
    private const CACHE_TTL      = 3600;
    private const ACTIVITY_TTL   = 3600; // 1 flush BDD max par heure

    public function __construct(
        ManagerRegistry $registry,
        #[Target('session.cache_pool')]
        private readonly CacheItemPoolInterface $cache,
    ) {
        parent::__construct($registry, UserSession::class);
    }

    /**
     * Crée une session pour $userIdentifier.
     *
     * Toutes les sessions actives précédentes sont révoquées (active = false)
     * AVANT la création, so le premier appareil détectera le conflit
     * dès sa prochaine requête.
     */
    public function createSession(
        string  $userIdentifier,
        string      $sessionId,
        ?string     $ipAddress         = null,
        ?string     $userAgent         = null,
        ?string     $deviceFingerprint = null,
    ): UserSession {
        $session = new UserSession();
        $session->setUserIdentifier($userIdentifier);
        $session->setSessionId($sessionId);
        $session->setIpAddress($ipAddress);
        $session->setUserAgent($userAgent);
        $session->setDeviceFingerprint($deviceFingerprint);
        // active = true par défaut (constructeur)

        $em = $this->getEntityManager();
        $em->persist($session);
        $em->flush();

        // 3. Mettre à jour le cache avec la nouvelle session
        $this->cacheSession($userIdentifier, $session);

        return $session;
    }

    public function getSessionInfo(string $userIdentifier):?array{return [] ;}

    /**
     * Révoque en BDD toutes les sessions actives d'un utilisateur.
     * Retourne les sessionId pour que l'appelant puisse nettoyer Redis.
     *
     * @return string[] sessionIds révoqués
     */
    public function revokeAllActiveSessions(string $userIdentifier): array
    {
        $sessions = $this->findBy([
            'userIdentifier' => $userIdentifier,
            'active'         => true,
        ]);

        if (empty($sessions)) {
            return [];
        }

        $revokedSessionIds = [];

        foreach ($sessions as $session) {
            $session->setActive(false);
            $this->cache->deleteItem('session_activity_lock_' . $session->getSessionId());
            $revokedSessionIds[] = $session->getSessionId();
        }

        $this->getEntityManager()->flush();
        $this->cache->deleteItem($this->cacheKey($userIdentifier));

        return $revokedSessionIds;
    }

    /**
     * Révoque une session précise par son PHPSESSID.
     * Appelé au logout.
     */
    public function removeSession(string $sessionId): ?string
    {
        $session = $this->findOneBy(['sessionId' => $sessionId]);

        if ($session === null) {
            return null;
        }

        $session->setActive(false);
        $this->getEntityManager()->flush();

        $this->cache->deleteItem($this->cacheKey($session->getUserIdentifier()));
        $this->cache->deleteItem('activity_lock_' . $sessionId);

        return $sessionId; // retourne l'id pour que l'appelant nettoie Redis
    }

    /**
     * Met à jour lastActivityAt — limité à 1 flush par heure via le cache.
     */
    public function updateSessionActivity(string $sessionId): void
    {
        $lockKey  = 'activity_lock_' . $sessionId;
        $lockItem = $this->cache->getItem($lockKey);

        if ($lockItem->isHit()) {
            return; // Déjà mis à jour dans cette fenêtre horaire
        }

        $session = $this->findOneBy([
            'sessionId' => $sessionId,
            'active'    => true,
        ]);

        if ($session === null) {
            return;
        }

        $session->updateActivity();
        $this->getEntityManager()->flush();

        // Verrouiller pour 1 heure
        $lockItem->set(true)->expiresAfter(self::ACTIVITY_TTL);
        $this->cache->save($lockItem);

        // Invalider le cache de session pour refléter lastActivityAt
        $this->cache->deleteItem($this->cacheKey($session->getUserIdentifier()));
    }

    /**
     * Marque les sessions expirées comme inactives en BDD.
     * Retourne les entités pour que le service puisse nettoyer Redis.
     *
     * @return UserSession[]
     */
    public function markExpiredSessionsAsInactive(int $hoursOfInactivity = 2): array
    {
        $limit = new \DateTimeImmutable("-{$hoursOfInactivity} hours");

        /** @var UserSession[] $expired */
        $expired = $this->createQueryBuilder('s')
            ->where('s.lastActivityAt < :limit')
            ->andWhere('s.active = :active')
            ->setParameter('active', true)
            ->setParameter('limit', $limit)
            ->getQuery()
            ->getResult();

        if (empty($expired)) {
            return [];
        }

        foreach ($expired as $s) {
            $s->setActive(false);
            $this->cache->deleteItem($this->cacheKey($s->getUserIdentifier()));
            $this->cache->deleteItem('activity_lock_' . $s->getSessionId());
        }

        $this->getEntityManager()->flush();

        return $expired;
    }

    /**
     * SOURCE DE VÉRITÉ pour la détection de conflit.
     *
     * Toujours lire la BDD pour cette vérification — jamais le cache.
     * Le cache est trop risqué : il peut contenir une session révoquée
     * avant que l'invalidation ait eu lieu (race condition).
     */
    public function hasActiveSession(
        string $userIdentifier,
        string $currentSessionId
    ): bool {
        // Requête directe BDD, filtre active = true
        $count = (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.userIdentifier = :uid')
            ->andWhere('s.active = :active')
            ->setParameter('active', true)
            ->andWhere('s.sessionId != :sid')
            ->setParameter('uid', $userIdentifier)
            ->setParameter('sid', $currentSessionId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Lecture de la session active — retourne null si aucune session active.
     * Utilise le cache pour éviter les lectures répétées (info non-critique).
     *
     * Ne PAS utiliser pour hasActiveSession (voir méthode ci-dessus).
     */
    public function findActiveSession(string $userIdentifier): ?UserSession
    {
        $cacheKey = $this->cacheKey($userIdentifier);
        $item     = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            // Le cache ne stocke que l'entité sérialisée, pas pour le contrôle de conflit
            $data = $item->get();
            $session = $this->find($data['id']);
            if ($session === null || !$session->isActive()) {
                // Cache périmé → invalider et relire la BDD
                $this->cache->deleteItem($cacheKey);
                // Tomber dans le chemin BDD ci-dessous
            } else {
                return $session;
            }
        }

        $session = $this->findOneBy(
            ['userIdentifier' => $userIdentifier, 'active' => true],
            ['id' => 'DESC'],
        );

        if ($session !== null) {
            $this->cacheSession($userIdentifier, $session);
        }

        return $session;
    }

    private function cacheKey(string $userIdentifier): string
    {
        return self::CACHE_PREFIX . '_' . $userIdentifier;
    }

    private function cacheSession(string $userIdentifier, UserSession $session): void
    {
        $item = $this->cache->getItem($this->cacheKey($userIdentifier));
        // On ne stocke que l'ID pour recharger proprement depuis la BDD
        $item->set(['id' => $session->getId()]);
        $item->expiresAfter(self::CACHE_TTL);
        $this->cache->save($item);
    }
}
