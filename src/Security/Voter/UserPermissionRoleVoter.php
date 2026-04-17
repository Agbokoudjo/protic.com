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

use App\Entity\SonataUser;
use App\Entity\UserPermissionRole;
use App\Security\Voter\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * UserPermissionRoleVoter
 *
 * Contrôle qui peut ATTRIBUER / VOIR / RÉVOQUER une UserPermissionRole
 * (l'association entre un utilisateur et une PermissionRole du catalogue).
 *
 * Règles métier ProTIC :
 * ┌─────────────────────────────────────────────────────────────────────────────┐
 * │ ROLE_SUPER_ADMIN (Fondateur)                                                │
 * │   → toutes les opérations sans restriction                                  │
 * │                                                                             │
 * │ ROLE_DIRECTOR                                                               │
 * │   → LIST, VIEW, CREATE, EDIT, DELETE sur toutes les attributions            │
 * │   → NE PEUT PAS attribuer ROLE_SUPER_ADMIN / ROLE_DIRECTOR à quelqu'un     │
 * │                                                                             │
 * │ ROLE_*_MGR                                                                  │
 * │   → LIST, VIEW : toutes les attributions                                    │
 * │   → CREATE     : uniquement si la PermissionRole.context === son pôle      │
 * │                  ET qu'il possède lui-même cette PermissionRole             │
 * │   → EDIT/DELETE: uniquement les attributions qu'IL a lui-même créées       │
 * │                  ET dont le contexte est le sien                            │
 * │                                                                             │
 * │ Agents                                                                      │
 * │   → VIEW uniquement sur ses propres attributions                           │
 * └─────────────────────────────────────────────────────────────────────────────┘
 *
 * Principe fondamental : on ne peut attribuer que ce qu'on possède soi-même,
 * et uniquement dans son périmètre de pôle.
 *
 * @extends Voter<string, UserPermissionRole|null>
 */
final class UserPermissionRoleVoter extends Voter implements Permission
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, self::ADMIN_USER_PERMISSION, true)) {
            return false;
        }

        // LIST / EXPORT : pas besoin d'objet
        if (in_array($attribute, [self::PERMISSION_LIST, self::PERMISSION_EXPORT], true)) {
            return true;
        }

        return $subject instanceof UserPermissionRole || $subject === null;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $actor = $token->getUser();

        if (!$actor instanceof SonataUser) {
            return false;
        }

        // Fondateur : accès total
        if ($actor->isSuperAdmin() || $actor->isFounder()) {
            return true;
        }

        // Directeur
        if ($actor->isDirector()) {
            return $this->directorCan($attribute, $subject);
        }

        // Responsables de pôle
        if ($this->isManager($actor)) {
            return $this->managerCan($attribute, $actor, $subject);
        }

        // Agents : lecture de ses propres attributions uniquement
        return $this->agentCan($attribute, $actor, $subject);
    }

    /**
     * Ce que le Directeur peut faire sur UserPermissionRole.
     *
     * Garde-fou : le Directeur ne peut pas s'attribuer à lui-même ROLE_DIRECTOR
     * ou attribuer ce rôle à quelqu'un d'autre via ce formulaire.
     * Cette règle est portée par le formulaire Sonata (filtrage des choix)
     * ET par ce Voter pour la couche métier.
     */
    private function directorCan(string $attribute, mixed $subject): bool
    {
        if (in_array($attribute, [
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
        ], true)) {
            // Vérification supplémentaire pour CREATE/EDIT :
            // bloquer si la permission associée pointe vers un rôle réservé au Fondateur
            if ($subject instanceof UserPermissionRole
                && in_array($attribute, [self::PERMISSION_CREATE, self::PERMISSION_EDIT], true)
            ) {
                return !$this->isReservedFounderPermission($subject);
            }

            return true;
        }

        return false;
    }

    /**
     * Ce qu'un responsable de pôle peut faire.
     *
     * Règle clé : on ne peut attribuer QUE ce qu'on possède soi-même,
     * dans son contexte de pôle uniquement.
     */
    private function managerCan(string $attribute, SonataUser $actor, mixed $subject): bool
    {
        // Lecture : toujours autorisée
        if (in_array($attribute, [self::PERMISSION_LIST, self::PERMISSION_VIEW, self::PERMISSION_EXPORT], true)) {
            return true;
        }

        // CREATE : la PermissionRole associée doit être dans le contexte du manager
        if ($attribute === self::PERMISSION_CREATE) {
            if ($subject === null) {
                // Sans objet cible, on autorise (le formulaire restreint les choix)
                return true;
            }

            if ($subject instanceof UserPermissionRole) {
                return $this->managerOwnsPermissionContext($actor, $subject);
            }

            return false;
        }

        // EDIT / DELETE / UNDELETE : uniquement les attributions créées dans son pôle
        if (in_array($attribute, [self::PERMISSION_EDIT, self::PERMISSION_DELETE, self::PERMISSION_UNDELETE], true)) {
            if (!$subject instanceof UserPermissionRole) {
                return false;
            }

            // Le manager doit avoir créé cette attribution (via assignedByUser)
            // ET la permission doit être dans son contexte
            return $this->actorIsAssigner($actor, $subject)
                && $this->managerOwnsPermissionContext($actor, $subject);
        }

        return false;
    }

    /**
     * Les agents ne peuvent voir que leurs propres attributions de permissions.
     */
    private function agentCan(string $attribute, SonataUser $actor, mixed $subject): bool
    {
        if ($attribute === self::PERMISSION_LIST) {
            return true; // filtre appliqué côté datagrid dans l'Admin
        }

        if ($attribute === self::PERMISSION_VIEW) {
            if (!$subject instanceof UserPermissionRole) {
                return false;
            }

            return (string) $subject->getUserId() === (string) $actor->getId();
        }

        return false;
    }

    /**
     * Vérifie que la PermissionRole associée à une UserPermissionRole
     * appartient au contexte de pôle du manager acteur.
     */
    private function managerOwnsPermissionContext(SonataUser $actor, UserPermissionRole $upr): bool
    {
        $permRole = $upr->getPermissionRole();
        $context  = $permRole->getContext();

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
     * Vérifie que l'acteur est bien celui qui a créé l'attribution
     * (via PermissionRole.createdBy).
     */
    private function actorIsAssigner(SonataUser $actor, UserPermissionRole $upr): bool
    {
        $assignedBy = $upr->getAssignedByUser();

        if (!$assignedBy instanceof SonataUser) {
            return false;
        }

        return (string) $assignedBy->getId() === (string) $actor->getId();
    }

    /**
     * Vérifie si la UserPermissionRole concerne une permission réservée
     * au Fondateur (aucun rôle ne peut l'attribuer via ce formulaire).
     *
     * Ex : une permission dont le name contient "SUPER_ADMIN" ou "FOUNDER".
     */
    private function isReservedFounderPermission(UserPermissionRole $upr): bool
    {
        $name = strtoupper($upr->getPermissionRole()->getName());

        return str_contains($name, 'SUPER_ADMIN')
            || str_contains($name, 'FOUNDER');
    }

    /**
     * Détermine si l'acteur est un responsable de pôle.
     */
    private function isManager(SonataUser $actor): bool
    {
        foreach (['ROLE_EDITORIAL_MGR', 'ROLE_CONTENT_MGR', 'ROLE_MODERATION_MGR', 'ROLE_TECH_MGR'] as $role) {
            if (in_array($role, $actor->getRoles(), true)) {
                return true;
            }
        }

        return false;
    }
}
