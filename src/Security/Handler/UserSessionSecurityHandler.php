<?php

declare(strict_types=1);

namespace App\Security\Handler;

use App\Security\Handler\AbstractRoleSecurityHandler;
use Sonata\AdminBundle\Admin\AdminInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class UserSessionSecurityHandler extends AbstractRoleSecurityHandler
{
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        parent::__construct($authorizationChecker);
    }

    public function isGranted(AdminInterface $admin, $attribute, ?object $object = null): bool
    {
        return true ;
    }
}
