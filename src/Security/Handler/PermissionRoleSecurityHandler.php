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

use App\Entity\PermissionRole;
use App\Security\Handler\AbstractRoleSecurityHandler;
use App\Security\Voter\PermissionRoleVoter;
use Sonata\AdminBundle\Admin\AdminInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * PermissionRoleSecurityHandler
 *
 * SecurityHandler dédié à PermissionRoleAdmin.
 *
 * Stratégie de dispatch :
 *
 *   1. Si l'attribut est un ROLE_* Symfony (ex: ROLE_ADMIN) ou si $object
 *      est lui-même un AdminInterface → on délègue à la hiérarchie Symfony
 *      standard via isGrantedRoleHierarchy().
 *
 *   2. Si l'attribut est une permission CRUD Sonata (VIEW, EDIT, CREATE…)
 *      → on délègue au PermissionRoleVoter qui applique les règles métier
 *      ProTIC (Fondateur > Directeur > Manager de pôle > Agent).
 *
 *   3. Fallback : hiérarchie standard.
 *
 * Enregistrement dans PermissionRoleAdmin :
 *   #[AutoconfigureTag(attributes: ['security_handler' => PermissionRoleSecurityHandler::class])]
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
final class PermissionRoleSecurityHandler extends AbstractRoleSecurityHandler
{
    public function __construct(
        private readonly Security $security,
        AuthorizationCheckerInterface $authorizationChecker,
    ) {
        parent::__construct($authorizationChecker);
    }

    /**
     * Point d'entrée unique appelé par Sonata pour toute vérification de droit.
     *
     * @param AdminInterface<object> $admin
     * @param string|string[]        $attribute  Permission Sonata (VIEW, EDIT…) ou ROLE_*
     * @param object|null            $object     Entité PermissionRole ou null
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
            // Résolution de la constante : Permission::PERMISSION_VIEW, etc.
            $permConstant = \constant(PermissionRoleVoter::class . '::PERMISSION_' . $attribute);

            return $this->security->isGranted(
                $permConstant,
                $object instanceof PermissionRole ? $object : null,
            );
        }

        // Cas 3 : fallback sur la hiérarchie standard
        return $this->isGrantedRoleHierarchy($admin, $attribute, $object);
    }

    // ---------------------------------------------------------------- HELPERS

    /**
     * Vérifie si l'attribut correspond à une constante PERMISSION_* de l'interface Permission.
     * Sonata passe des chaînes comme "VIEW", "EDIT", "LIST", "CREATE", "DELETE"…
     */
    private function isCrudPermission(string $attribute): bool
    {
        return \defined(PermissionRoleVoter::class . '::PERMISSION_' . $attribute);
    }
}
