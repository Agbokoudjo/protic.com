<?php

declare(strict_types=1);

/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * Phone: +229 01 67 25 18 86
 * LinkedIn: https://www.linkedin.com/in/internationales-web-apps-services-120520193/
 * Github: https://github.com/Agbokoudjo/
 * Company: INTERNATIONALES WEB APPS & SERVICES
 *
 * For more information, please feel free to contact the author.
 */

namespace App\Security\Authorization;

use App\Domain\BaseUserInterface;
use App\Repository\UserPermissionRoleRepository;
use App\Security\Authorization\AuthorizationCheckerForUserInterface;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
final class AuthorizationCheckerForUser implements AuthorizationCheckerForUserInterface
{
    public function __construct(private readonly UserPermissionRoleRepository $userPermissionRoleRepo)
    {
        
    }

    public function isGrantedForUser(BaseUserInterface $user,string $role):bool{
        
        return $user->hasRole($role) 
            || $this->userPermissionRoleRepo->userHasRole($user->getId(),$role);
    }   
}
