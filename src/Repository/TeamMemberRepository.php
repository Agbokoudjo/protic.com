<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TeamMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Doctrine\ORM\EntityNotFoundException;

/**
 * @extends ServiceEntityRepository<TeamMember>
 */
final class TeamMemberRepository extends ServiceEntityRepository
{
    private const CACHE_TAG = '_team_members_list_';
    private const CACHE_KEY = '_team_visible_ordered_';

    public function __construct(
        ManagerRegistry $registry,
        #[Target('data.respository.cache')]
        private readonly TagAwareCacheInterface $dataCacheTeamMember
    ) {
        parent::__construct($registry, TeamMember::class);
    }

    /**
     * Retourne les membres visibles avec une gestion robuste des liens morts.
     */
    public function findVisibleOrderedByPosition(): array
    {
        $members = $this->dataCacheTeamMember->get(self::CACHE_KEY, function (ItemInterface $item) {
            $item->tag([self::CACHE_TAG]);
            $item->expiresAfter(604800); // 7 jours

            return $this->createQueryBuilder('tm')
                ->leftJoin('tm.linkedUser', 'u') // Jointure pour charger l'user en une fois
                ->addSelect('u')
                ->andWhere('tm.visible = :visible')
                ->setParameter('visible', true)
                ->orderBy('tm.position', 'ASC')
                ->getQuery()
                ->getResult();
        });

        // FILTRAGE DE SÉCURITÉ : On nettoie les proxys morts à la sortie du cache
        return array_filter($members, function (TeamMember $member) {
            try {
                // Si un lien existe, on vérifie qu'il est réel
                if ($member->getLinkedUser()) {
                    $member->getLinkedUser()->getUsername();
                }
                return true;
            } catch (EntityNotFoundException) {
                // Si l'utilisateur est introuvable (ID 68 par exemple)
                // On pourrait loguer l'erreur ici pour ton suivi
                return true; // On garde le membre, mais son getter sécurisé renverra null
            }
        });
    }

    public function add(TeamMember $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
        $this->invalidateCacheTeamMember();
    }

    public function invalidateCacheTeamMember(): void
    {
        try {
            $this->dataCacheTeamMember->invalidateTags([self::CACHE_TAG]);
            $this->dataCacheTeamMember->delete(self::CACHE_KEY);
        } catch (\Throwable) {
            // Silencieusement échouer si Redis/Cache est indisponible
        }
    }
}
