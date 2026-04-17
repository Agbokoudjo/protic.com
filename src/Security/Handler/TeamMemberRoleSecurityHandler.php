<?php

declare(strict_types=1);

namespace App\Security\Handler;

use App\Security\Handler\AbstractRoleSecurityHandler;
use App\Security\Voter\TeamMemberVoter;
use Sonata\AdminBundle\Admin\AdminInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class TeamMemberRoleSecurityHandler extends AbstractRoleSecurityHandler
{
    public function __construct(
        private Security $security,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        parent::__construct($authorizationChecker);
    }

    public function isGranted(AdminInterface $admin, $attribute, ?object $object = null): bool
    {
        if (
            $object instanceof AdminInterface
            || (\is_string($attribute) && str_starts_with($attribute, 'ROLE_'))
        ) {
            return $this->isGrantedRoleHierarchy($admin, $attribute, $object);
        }
        
        /**
         * @var TeamMemberVoter
         */
        $perm = \constant(TeamMemberVoter::class . '::PERMISSION_' . $attribute);
        return $this->security->isGranted($perm, $object);
    }
}
