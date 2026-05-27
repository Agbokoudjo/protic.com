<?php

declare(strict_types=1);

namespace App\Security\Handler;

use App\Security\Handler\AbstractRoleSecurityHandler;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class SonataRoleSecurityHandler extends AbstractRoleSecurityHandler
{
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        parent::__construct($authorizationChecker);
    }
}
