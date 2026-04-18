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
 * Service percutant pour la synchronisation User <-> TeamMember.
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
final readonly class TeamMemberSyncHandler
{
    public function __construct(
        private TeamMemberRepository $teamMemberRepository,
        private SonataUserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Crée ou met à jour le membre de l'équipe de manière atomique.
     */
    public function createOrUpdateTeamMember(int|string $userId): void
    {
        $user = $this->userRepository->find($userId);
        if (!$user instanceof SonataUser) {
            return;
        }

        // 1. Récupération ou Création (Atomicité)
        $teamMember = $this->teamMemberRepository->findOneBy(['linkedUser' => $user]) ?? new TeamMember();

        if (null === $teamMember->getId()) {
            $teamMember->setLinkedUser($user);
            $teamMember->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        }

        // 2. Mapping Percutant (Direct & Propre)
        $teamMember->setName($user->getUsername() ?? 'Membre Anonyme')
            ->setRole($user->getProfile() ?? 'Collaborateur')
            ->setBio($user->getTeamBio())
            ->setInitial($user->getTeamInitial() ?? $this->extractInitial($user->getUsername()))
            ->setAltText($user->getTeamAltText() ?? sprintf("Photo de %s", $user->getUsername()))
            ->setPosition($user->getTeamPosition() ?? 99)
            ->setVisible(true)
            ->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));

        // 3. Gestion de l'Avatar (VichUploader Mirroring)
        if ($user->getAvatarName()) {
            $teamMember->setImageName($user->getAvatarName());
            $teamMember->setImageUpdatedAt($user->getAvatarUpdatedAt());
        }

        // 4. Persistance & Invalidation Cache
        $this->entityManager->persist($teamMember);
        $this->entityManager->flush();

        // Nettoyage immédiat du cache Redis pour le Front (React)
        $this->teamMemberRepository->invalidateCacheTeamMember();
    }

    /**
     * Désactive la visibilité au lieu de supprimer (Soft deactivation).
     */
    public function deactivateTeamMember(int|string $userId): void
    {
        $user = $this->userRepository->find($userId);
        $teamMember = $user ? $this->teamMemberRepository->findOneBy(['linkedUser' => $user]) : null;

        if ($teamMember) {
            $teamMember->setVisible(false);
            $teamMember->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));

            $this->entityManager->flush();
            $this->teamMemberRepository->invalidateCacheTeamMember();
        }
    }

    /**
     * Extraction intelligente d'initiales (Gestion UTF-8 & Accents).
     */
    private function extractInitial(?string $name): string
    {
        $cleanName = trim($name ?? '');
        if ($cleanName === '') return '?';

        // Prend la première lettre du premier mot et du dernier mot si présent
        $words = preg_split('/\s+/', $cleanName, -1, PREG_SPLIT_NO_EMPTY);
        $first = mb_substr($words[0], 0, 1, 'UTF-8');
        $last = (count($words) > 1) ? mb_substr(end($words), 0, 1, 'UTF-8') : '';

        return mb_strtoupper($first . $last, 'UTF-8');
    }
}