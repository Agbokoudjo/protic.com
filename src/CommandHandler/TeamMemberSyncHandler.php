<?php

declare(strict_types=1);

/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * For more information, please feel free to contact the author.
 */

namespace App\CommandHandler;

use App\Entity\SonataUser;
use App\Entity\TeamMember;
use App\Repository\SonataUserRepository;
use App\Repository\TeamMemberRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Synchronise automatiquement l'entité TeamMember lorsqu'un SonataUser
 * est créé ou modifié avec isMember = true / false.
 *
 * Règles métier :
 *  - isMember passe à TRUE  → crée un TeamMember lié (s'il n'existe pas déjà)
 *  - isMember passe à FALSE → rend le TeamMember invisible (visible=false), ne supprime pas
 *  - Champs team* modifiés  → met à jour le TeamMember existant
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
final class TeamMemberSyncHandler
{
    public function __construct(
        private readonly TeamMemberRepository $teamMemberRepository,
        private readonly SonataUserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function createOrUpdateTeamMember(int|string $userId): void
    {
        $user = $this->userRepository->find($userId) ;
        if(!($user instanceof SonataUser)) { return ;}

        // Cherche un TeamMember déjà lié à cet utilisateur
        $teamMember = $this->teamMemberRepository->findOneBy(['linkedUser' => $user]);

        if (null === $teamMember) {
            $teamMember = new TeamMember();
            $teamMember->setLinkedUser($user);
            $teamMember->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        } else {
            $teamMember->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        }

        // Synchronisation des champs depuis SonataUser → TeamMember
        $teamMember->setName($user->getUsername() ?? '');
        $teamMember->setRole($user->getProfile() ?? '');
        $teamMember->setBio($user->getTeamBio());
        $teamMember->setInitial($user->getTeamInitial() ?? $this->extractInitial($user->getUsername()));
        $teamMember->setAltText($user->getTeamAltText());
        $teamMember->setPosition($user->getTeamPosition() ?? 99);
        $teamMember->setVisible(true);

        // Synchronisation de la photo si elle existe sur SonataUser
        if (null !== $user->getAvatarName()) {
            $teamMember->setImageName($user->getAvatarName());
            $teamMember->setImageUpdatedAt($user->getAvatarUpdatedAt());
        }

        // Flush dans un nouveau contexte pour éviter les conflits avec
        // le flush en cours de l'événement postUpdate/postPersist
        $this->teamMemberRepository->add($teamMember);
    }

    public function deactivateTeamMember(int|string $userId): void
    {
        $user = $this->userRepository->find($userId) ;
        if(!($user instanceof SonataUser)) { return ;}

        //Recherche du membre de l'équipe
        $teamMember = $this->teamMemberRepository->findOneBy(['linkedUser' => $user]);
        if (null === $teamMember) {
            return;
        }

        $teamMember->setVisible(false);
        $teamMember->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));

        $this->entityManager->flush();
    }

    /**
     * Génère une initiale à partir du nom complet.
     * Ex: "HOUNGNIMON Denis" → "H"
     */
    private function extractInitial(?string $fullName): string
    {
        if (null === $fullName || '' === trim($fullName)) {
            return '?';
        }

        $firstChar = mb_strtoupper(mb_substr(trim($fullName), 0, 1, 'UTF-8'), 'UTF-8');

        return $firstChar ?: '?';
    }
}
