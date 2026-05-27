<?php

declare(strict_types=1);

/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * Phone: +229 01 67 25 18 86
 * LinkedIn: https://www.linkedin.com/in/internationales-web-apps-services-120520193/
 * Github: https://github.com/Agbokoudjo/
 * Company: INTERNATIONALES WEB APPS & SERVICES
 *
 * For more information, please feel free to contact the author.
 */

namespace App\EventSubscriber;

use App\CommandHandler\TeamMemberSyncHandler;
use App\CommandHandler\UpdatePasswordUserHandler;
use App\CommandHandler\UpdateUserProfileHandler;
use App\Entity\BaseUserInterface;
use App\Entity\SonataUser;
use App\Entity\TeamMember;
use App\Queue\AsyncMethodDispatcherInterface;
use App\Repository\TeamMemberRepository;
use App\Security\Provider\UserProvider;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Event\PersistenceEvent;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Event Subscriber pour les événements Doctrine des utilisateurs
 * 
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
final  class UserEventSubscriber implements EventSubscriberInterface
{
    /**
     * Stocke les changeSets capturés dans preUpdate, indexés par userId.
     * Structure : [ userId => ['field' => [oldValue, newValue], ...] ]
     *
     * @var array<int|string, array<string, array{0: mixed, 1: mixed}>>
     */
    private array $pendingChangeSets = [];

    /**
     * Champs surveillés pour la sync TeamMember.
     * Clé = nom PHP, Valeur = colonne SQL dans sonata_user.
     */
    private const TEAM_FIELDS_MAP = [
        'isMember'     => 'is_member',
        'teamBio'      => 'team_bio',
        'teamPosition' => 'team_position',
        'teamInitial'  => 'team_initial',
        'teamAltText'  => 'team_alt_text',
        'avatarName'   => 'avatar_name',
        'username'     => 'username',
        'profile'      => 'profile',
    ];

    /**
     * Délai en millisecondes avant l'exécution de l'async UpdatePasswordUserHandler .
     * Laisse le temps à Sonata de finaliser son flush + commit BDD.
     * 45 secondes = largement suffisant dans tous les cas.
     */
    private const UPDATE_PASSWORD_SYNC_DELAY_MS = 45000;

    /**
     * Délai en millisecondes avant l'exécution de l'async TeamMemberSync.
     * Laisse le temps à Sonata de finaliser son flush + commit BDD.
     * 35 secondes = largement suffisant dans tous les cas.
     */
    private const TEAM_SYNC_DELAY_MS = 35000;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TeamMemberRepository $teamMemberRepository,
        private readonly AsyncMethodDispatcherInterface $asyncMethodDispatcher,
        private readonly UserProvider $userProvider,
        private readonly ParameterBagInterface $parameterBag
        ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'sonata.admin.event.persistence.post_update' => ['postUpdate',500],
            'sonata.admin.event.persistence.pre_update' => ['preUpdate', 500],
            'sonata.admin.event.persistence.post_persist'=>['postPersit',500],
            'sonata.admin.event.persistence.pre_remove' => 'onPreRemove'
          
        ];
    }

    final public function postPersit(PersistenceEvent $sonataEvent): void
    {
        $entity = $sonataEvent->getObject();
        if (!$entity instanceof SonataUser) return;

        $this->dispatchUpdateCommand($entity);

        if (!$entity->isMember()) {
            return;
        }

        $this->asyncMethodDispatcher->dispatch(TeamMemberSyncHandler::class, 'createOrUpdateTeamMember', [$entity->getId()]);
    }

    final public function preUpdate(PersistenceEvent $event):void {

        $adminUser=$event->getObject() ;
        if (!($adminUser instanceof SonataUser)) return;

        $plainPassword = $adminUser->getPlainPassword();
        if ($plainPassword) {
            $this->asyncMethodDispatcher->dispatch(
                UpdatePasswordUserHandler::class,
                'handle',
                [
                    $adminUser->getId(),
                    $plainPassword
                ],
                new \DateTimeImmutable(
                    sprintf('+%d milliseconds', self::UPDATE_PASSWORD_SYNC_DELAY_MS),
                    new \DateTimeZone('UTC')
                )
            );
        }
        $userId = $adminUser->getId();
        // ── Lecture des vraies valeurs BDD via DBAL (bypass identity map) ─────
        $persistedRow = $this->fetchPersistedRowFromDb($userId);
        
        if (null === $persistedRow) {
            return;
        }

        // ── Comparaison avec l'objet soumis ───────────────────────────────────
        $diff = $this->computeDiffFromRow($persistedRow, $adminUser);

        if ([] === $diff) {
           return ;
        }

        // ── Calcul du délai d'exécution async ────────────────────────────────
        // On crée une date dans 35 secondes pour que le DelayStamp soit calculé
        // correctement par AsyncMethodDispatcher::dispatch().
        $executeAfter = new \DateTimeImmutable(
            sprintf('+%d milliseconds', self::TEAM_SYNC_DELAY_MS),
            new \DateTimeZone('UTC')
        );

        // ── Dispatch async avec délai ─────────────────────────────────────────
        if (!$adminUser->isMember()) {
            $this->asyncMethodDispatcher->dispatch(
                TeamMemberSyncHandler::class,
                'deactivateTeamMember',
                [$userId],
                $executeAfter
            );
            return;
        }

        $this->asyncMethodDispatcher->dispatch(
            TeamMemberSyncHandler::class,
            'createOrUpdateTeamMember',
            [$userId],
            $executeAfter
        );
    }

    final public function postUpdate(PersistenceEvent $sonataEvent): void
    {
        $entity=$sonataEvent->getObject();
        if (!$entity instanceof SonataUser) return;

        $this->dispatchUpdateCommand($entity);
        $this->userProvider->invalidateUserCache($entity->getId());
    }
    
    private function dispatchUpdateCommand(BaseUserInterface $entity): void
    {
        $this->asyncMethodDispatcher->dispatch(UpdateUserProfileHandler::class, 'handle', [$entity->getId()]);
    }

    /**
     * Lit directement les colonnes team_* depuis la BDD via DBAL.
     * Bypasse totalement l'identity map ORM → retourne les vraies valeurs
     * encore en BDD avant que Sonata ne fasse son flush.
     *
     * @return array<string, mixed>|null
     */
    private function fetchPersistedRowFromDb(int|string $userId): ?array
    {
        /** @var Connection $conn */
        $conn = $this->em->getConnection();

        $columns = implode(', ', array_values(self::TEAM_FIELDS_MAP));

        $sql = sprintf(
            'SELECT %s FROM sonata_user WHERE id = :id LIMIT 1',
            $columns
        );

        $row = $conn->fetchAssociative($sql, ['id' => $userId]);

        return $row ?: null;
    }

    /**
     * Compare les valeurs BDD brutes (colonnes SQL) avec les getters de l'objet
     * soumis par le formulaire Sonata.
     *
     * @param array<string, mixed> $persistedRow Résultat DBAL (clés = noms de colonnes SQL)
     * @return array<string, array{old: mixed, new: mixed}>
     */
    private function computeDiffFromRow(array $persistedRow, SonataUser $new): array
    {
        // Mapping colonne SQL → getter PHP
        $fieldGetterMap = [
            'is_member'     => fn(SonataUser $u) => $u->isMember(),
            'team_bio'      => fn(SonataUser $u) => $u->getTeamBio(),
            'team_position' => fn(SonataUser $u) => $u->getTeamPosition(),
            'team_initial'  => fn(SonataUser $u) => $u->getTeamInitial(),
            'team_alt_text' => fn(SonataUser $u) => $u->getTeamAltText(),
            'avatar_name'   => fn(SonataUser $u) => $u->getAvatarName(),
            'username'      => fn(SonataUser $u) => $u->getUsername(),
            'profile'       => fn(SonataUser $u) => $u->getProfile(),
        ];

        $diff = [];

        foreach ($fieldGetterMap as $column => $getter) {
            if (!array_key_exists($column, $persistedRow)) {
                continue;
            }

            $oldRaw = $persistedRow[$column];
            $newVal = $getter($new);

            // Normalisation pour éviter les faux positifs :
            // DBAL retourne les booléens comme '1'/'0' ou true/false selon le driver
            $oldNormalized = $this->normalizeValue($oldRaw);
            $newNormalized = $this->normalizeValue($newVal);

            if ($oldNormalized !== $newNormalized) {
                // On stocke le nom PHP du champ (pas le nom SQL) pour lisibilité
                $phpField = array_search($column, self::TEAM_FIELDS_MAP, true) ?: $column;
                $diff[$phpField] = ['old' => $oldRaw, 'new' => $newVal];
            }
        }

        return $diff;
    }

    /**
     * Normalise une valeur pour la comparaison.
     * Gère les cas DBAL : booléen stocké en '1'/'0', null vs '', entiers en string.
     */
    private function normalizeValue(mixed $value): string
    {
        if (null === $value) {
            return '';
        }

        // Booléen PHP → '1' ou '0'
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    /**
     * Gère la sécurité des données lors de la suppression d'un SonataUser.
     * Si lié à un TeamMember, on transfère l'avatar pour ne pas perdre l'image.
     * * @author AGBOKOUDJO Franck
     */
    public function onPreRemove(PersistenceEvent $event): void
    {
        $user = $event->getObject();

        if ($user instanceof SonataUser) {
            $teamMembers = $this->teamMemberRepository->findBy(['linkedUser' => $user]);

            foreach ($teamMembers as $member) {
                $this->transferAvatarToTeamMember($user, $member);

                // On coupe le lien car l'User va disparaître
                $member->setLinkedUser(null);
            }

            $this->em->flush();
        }
    }

    /**
     * Copie physiquement l'avatar de l'User vers le stockage du TeamMember.
     */
    private function transferAvatarToTeamMember(SonataUser $user, TeamMember $member): void
    {
        $avatarName = $user->getAvatarName();
        if (!$avatarName) {
            return;
        }

        // 1. Définir les chemins (Basé sur ta config vich_uploader)
        $uploadDirAvatars = $this->parameterBag->get('kernel.project_dir') . '/public/uploads/avatars/';
        $uploadDirTeam = $this->parameterBag->get('kernel.project_dir') . '/public/uploads/team/';

        $sourcePath = $uploadDirAvatars . $avatarName;

        if (file_exists($sourcePath)) {
            // 2. Générer un nouveau nom pour éviter les collisions
            $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
            $newFileName = uniqid('transfer_', true) . '.' . $extension;
            $destinationPath = $uploadDirTeam . $newFileName;

            // 3. Copie physique du fichier
            if (copy($sourcePath, $destinationPath)) {
                // 4. On crée un objet File pour simuler un upload Symfony
                // Cela permet à VichUploader de détecter le changement
                $file = new UploadedFile(
                    $destinationPath,
                    $avatarName,
                    null,
                    null,
                    true // Mode test activé pour permettre l'usage d'un fichier déjà sur le disque
                );

                $member->setImageFile($file);
                $member->setImageName($newFileName);

                // Important : mettre à jour le nom pour que Vich ne cherche pas l'ancien
                $member->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
            }
        }
    }
}
