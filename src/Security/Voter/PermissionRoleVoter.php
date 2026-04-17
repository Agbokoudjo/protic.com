<?php

declare(strict_types=1);

/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * Phone: +229 01 67 25 18 86
 * Company: INTERNATIONALES WEB APPS & SERVICES
 */

namespace App\Security\Voter;

use App\Entity\PermissionRole;
use App\Entity\SonataUser;
use App\Security\Voter\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * PermissionRoleVoter
 *
 * Contrôle qui peut CRÉER / LIRE / MODIFIER / SUPPRIMER une entité PermissionRole
 * (les "permissions nommées" du catalogue, ex : PUBLICATION_CREATE, COMMENT_DELETE…)
 *
 * Règles métier ProTIC :
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │ ROLE_SUPER_ADMIN (Fondateur) → toutes permissions sans restriction      │
 * │ ROLE_DIRECTOR                → LIST, VIEW, CREATE, EDIT, DELETE         │
 * │ ROLE_*_MGR                   → LIST, VIEW                               │
 * │                                CREATE / EDIT / DELETE uniquement si     │
 * │                                la permission appartient à SON contexte  │
 * │ Autres (agents)              → LIST, VIEW uniquement                    │
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * Le champ `context` de PermissionRole (ex : "editorial", "content"…) sert
 * à restreindre l'accès des responsables de pôle à leur domaine.
 *
 * @extends Voter<string, PermissionRole|null>
 */
final class PermissionRoleVoter extends Voter implements Permission
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        // Attributs gérés par ce Voter (correspondance avec Permission::ADMIN_USER_PERMISSION)
        if (!in_array($attribute, self::ADMIN_USER_PERMISSION, true)) {
            return false;
        }

        // Pour LIST / CREATE (admin-level), $subject peut être null
        if (in_array($attribute, [self::PERMISSION_LIST, self::PERMISSION_EXPORT], true)) {
            return true;
        }

        // Pour les actions sur un objet, on exige une PermissionRole
        return $subject instanceof PermissionRole;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $actor = $token->getUser();

        if (!$actor instanceof SonataUser) {
            return false;
        }

        // Fondateur : accès total, toujours
        if ($actor->isSuperAdmin() || $actor->isFounder()) {
            return true;
        }

        // Directeur : tous les droits CRUD sur toutes les PermissionRole
        if ($actor->isDirector()) {
            return $this->directorCan($attribute);
        }

        // Responsables de pôle : CRUD limité à leur contexte
        if ($this->isManager($actor)) {
            return $this->managerCan($attribute, $actor, $subject);
        }

        // Agents opérationnels : lecture seule
        return $this->agentCan($attribute);
    }

    /**
     * Ce que le Directeur peut faire sur PermissionRole.
     */
    private function directorCan(string $attribute): bool
    {
        return in_array($attribute, [
            self::PERMISSION_LIST,
            self::PERMISSION_VIEW,
            self::PERMISSION_CREATE,
            self::PERMISSION_EDIT,
            self::PERMISSION_DELETE,
            self::PERMISSION_UNDELETE,
            self::PERMISSION_EXPORT,
            self::PERMISSION_HISTORY,
            self::PERMISSION_OPERATOR,
            self::PERMISSION_MASTER,
        ], true);
    }

    /**
     * Ce qu'un responsable de pôle peut faire.
     * CREATE / EDIT / DELETE → uniquement si la permission est dans son contexte.
     * LIST / VIEW → toujours.
     */
    private function managerCan(string $attribute, SonataUser $actor, mixed $subject): bool
    {
        // Lecture : toujours autorisée pour un manager
        if (in_array($attribute, [self::PERMISSION_LIST, self::PERMISSION_VIEW, self::PERMISSION_EXPORT], true)) {
            return true;
        }

        // Écriture : uniquement dans son contexte
        if (in_array($attribute, [self::PERMISSION_CREATE, self::PERMISSION_EDIT, self::PERMISSION_DELETE, self::PERMISSION_UNDELETE], true)) {
            // Pour CREATE, pas d'objet existant à vérifier — on autorise dans son pôle
            if ($subject === null) {
                return true; // Le formulaire filtrera le contexte disponible
            }

            if (!$subject instanceof PermissionRole) {
                return false;
            }

            return $this->isContextOfManager($actor, $subject->getContext());
        }

        return false;
    }

    /**
     * Les agents opérationnels ne peuvent que lire la liste des permissions.
     */
    private function agentCan(string $attribute): bool
    {
        return in_array($attribute, [self::PERMISSION_LIST, self::PERMISSION_VIEW], true);
    }

    /**
     * Vérifie si le contexte d'une PermissionRole appartient au pôle du manager.
     *
     * Mapping pôle Symfony → contexte PermissionRole.context
     */
    private function isContextOfManager(SonataUser $actor, ?string $context): bool
    {
        if ($context === null) {
            return false;
        }

        $poleContextMap = [
            'ROLE_EDITORIAL_MGR'  => ['editorial'],
            'ROLE_CONTENT_MGR'    => ['content'],
            'ROLE_MODERATION_MGR' => ['moderation'],
            'ROLE_TECH_MGR'       => ['tech'],
        ];

        foreach ($poleContextMap as $role => $contexts) {
            if (in_array($role, $actor->getRoles(), true)
                && in_array($context, $contexts, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Détermine si l'acteur est un responsable de pôle (sans être Directeur).
     */
    private function isManager(SonataUser $actor): bool
    {
        $managerRoles = [
            'ROLE_EDITORIAL_MGR',
            'ROLE_CONTENT_MGR',
            'ROLE_MODERATION_MGR',
            'ROLE_TECH_MGR',
        ];

        foreach ($managerRoles as $role) {
            if (in_array($role, $actor->getRoles(), true)) {
                return true;
            }
        }

        return false;
    }
}
