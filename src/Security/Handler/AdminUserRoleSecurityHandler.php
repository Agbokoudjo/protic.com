<?php

declare(strict_types=1);

namespace App\Security\Handler;

use App\Security\Handler\AbstractRoleSecurityHandler;
use App\Security\Voter\AdminUserVoter;
use App\Security\Voter\PasswordUserVoter;
use Sonata\AdminBundle\Admin\AdminInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class AdminUserRoleSecurityHandler extends AbstractRoleSecurityHandler 
{
    public function __construct(
        private Security $security, 
        AuthorizationCheckerInterface $authorizationChecker)
    {
        parent::__construct($authorizationChecker);
    }

    public function isGranted(AdminInterface $admin, $attribute, ?object $object = null): bool
    {
        if ($object instanceof AdminInterface
            || (\is_string($attribute) && str_starts_with($attribute, 'ROLE_'))) {
            return $this->isGrantedRoleHierarchy($admin, $attribute, $object);
        }

        if (\is_string($attribute) && 
            str_starts_with($attribute, 'sonata_user_edit')
        ) {
            return $this->security->isGranted(PasswordUserVoter::EDIT_PASSWORD, $object) ;
        }
      
        /**
         * @var AdminUserVoter
         */
        $perm = \constant(AdminUserVoter::class . '::PERMISSION_' . $attribute);
        return $this->security->isGranted($perm, $object);
    }
}
