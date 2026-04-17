<?php

declare(strict_types=1);

/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * Phone: +229 01 67 25 18 86
 * Company: INTERNATIONALES WEB APPS & SERVICES
 */

namespace App\Security\Handler;

use App\Entity\UserPermissionRole;
use App\Security\Handler\AbstractRoleSecurityHandler;
use App\Security\Voter\UserPermissionRoleVoter;
use Sonata\AdminBundle\Admin\AdminInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * UserPermissionRoleSecurityHandler
 *
 * SecurityHandler dédié à UserPermissionRoleAdmin.
 *
 * Stratégie de dispatch — identique au pattern AdminUserRoleSecurityHandler :
 *
 *   1. ROLE_* ou $object instanceof AdminInterface
 *      → hiérarchie Symfony standard (isGrantedRoleHierarchy)
 *
 *   2. Permission CRUD Sonata (VIEW, EDIT, LIST, CREATE, DELETE…)
 *      → UserPermissionRoleVoter (règles métier granulaires ProTIC)
 *         · Fondateur  → tout
 *         · Directeur  → tout sauf permissions réservées Fondateur
 *         · Manager    → son contexte de pôle uniquement
 *         · Agent      → VIEW sur ses propres attributions
 *
 *   3. Fallback → hiérarchie standard
 *
 * Enregistrement dans UserPermissionRoleAdmin :
 *   #[AutoconfigureTag(attributes: ['security_handler' => UserPermissionRoleSecurityHandler::class])]
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
final class UserPermissionRoleSecurityHandler extends AbstractRoleSecurityHandler
{
    public function __construct(
        private readonly Security $security,
        AuthorizationCheckerInterface $authorizationChecker,
    ) {
        parent::__construct($authorizationChecker);
    }

    /**
     * @param AdminInterface<object> $admin
     * @param string|string[]        $attribute
     * @param object|null            $object     UserPermissionRole ou null
     */
    public function isGranted(AdminInterface $admin, $attribute, ?object $object = null): bool
    {
        // Cas 1 : vérification d'un rôle Symfony pur ou sur l'Admin lui-même
        if ($object instanceof AdminInterface
            || (\is_string($attribute) && str_starts_with($attribute, 'ROLE_'))
        ) {
            return $this->isGrantedRoleHierarchy($admin, $attribute, $object);
        }

        // Cas 2 : action CRUD Sonata → Voter métier
        if (\is_string($attribute) && $this->isCrudPermission($attribute)) {
            $permConstant = \constant(UserPermissionRoleVoter::class . '::PERMISSION_' . $attribute);

            return $this->security->isGranted(
                $permConstant,
                $object instanceof UserPermissionRole ? $object : null,
            );
        }

        // Cas 3 : fallback
        return $this->isGrantedRoleHierarchy($admin, $attribute, $object);
    }

    // ---------------------------------------------------------------- HELPERS

    private function isCrudPermission(string $attribute): bool
    {
        return \defined(UserPermissionRoleVoter::class . '::PERMISSION_' . $attribute);
    }
}
