<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security\Handler;

use Sonata\AdminBundle\Admin\AdminInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Sonata\AdminBundle\Security\Handler\SecurityHandlerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
class AbstractRoleSecurityHandler implements SecurityHandlerInterface
{

    public function __construct(private AuthorizationCheckerInterface $authorizationChecker) {}

    /**
     * @param AdminInterface $admin
     * @param string $attribute
     * @param object|null $object
     * @return boolean
     */
    public function isGranted(AdminInterface $admin, $attribute, ?object $object = null): bool{
        return $this->isGrantedRoleHierarchy($admin,$attribute,$object) ;
    }

    public function getBaseRole(AdminInterface $admin): string
    {
        return \sprintf('ROLE_%s_%%s', str_replace('.', '_', strtoupper($admin->getCode())));
    }

    public function buildSecurityInformation(AdminInterface $admin): array
    {
        return [];
    }

    public function createObjectSecurity(AdminInterface $admin, object $object): void {}

    public function deleteObjectSecurity(AdminInterface $admin, object $object): void {}

    /**
     * @param array<string|Expression> $attributes
     */
    protected  function isAnyGranted(array $attributes, ?object $subject = null): bool
    {
        foreach ($attributes as $attribute) {
            if ($this->authorizationChecker->isGranted($attribute, $subject)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string|Expression> $attributes
     */
    protected  function isGrantedRoleHierarchy(AdminInterface $admin,string $attribute, ?object $object = null): bool{

        $attributes = (array) $attribute;
        $useAll = $this->hasOnlyAdminRoles($attributes);
        $attributes = $this->mapAttributes($attributes, $admin);
        $allRole = \sprintf($this->getBaseRole($admin), 'ALL');
        try {
            return $this->isAnyGranted($attributes, $object)
                || ($useAll && $this->isAnyGranted([$allRole], $object));
        } catch (AuthenticationCredentialsNotFoundException) {
            return false;
        }
    }
    /**
     * @param array<string|Expression> $attributes
     */
    protected function hasOnlyAdminRoles(array $attributes): bool
    {
        // NEXT_MAJOR: Change the foreach to a single check.
        foreach ($attributes as $attribute) {
            // If the attribute is not already a ROLE_ we generate the related role.
            if (\is_string($attribute) && !str_starts_with($attribute, 'ROLE_')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string|Expression> $attributes
     * @param AdminInterface<object>   $admin
     *
     * @return array<string|Expression>
     */
    protected function mapAttributes(array $attributes, AdminInterface $admin): array
    {
        $mappedAttributes = [];

        foreach ($attributes as $attribute) {
            if (!\is_string($attribute) || str_starts_with($attribute, 'ROLE_')) {
                $mappedAttributes[] = $attribute;

                continue;
            }

            $baseRole = $this->getBaseRole($admin);

            $mappedAttributes[] = \sprintf($baseRole, $attribute);

            foreach ($admin->getSecurityInformation() as $role => $permissions) {
                if (\in_array($attribute, $permissions, true)) {
                    $mappedAttributes[] = \sprintf($baseRole, $role);
                }
            }
        }

        return array_unique($mappedAttributes);
    }
}
