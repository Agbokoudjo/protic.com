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

namespace App\Security\Voter;

use App\Entity\BaseUserInterface;
use App\Entity\SonataUser;
use App\Security\Authorization\AuthorizationCheckerForUser;
use App\Security\Voter\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */

final class AdminUserVoter extends Voter implements Permission
{
    public function __construct(
        private readonly AuthorizationCheckerForUser $authorizationCheckerForUser
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {

        return in_array($attribute, self::ADMIN_USER_PERMISSION)
            && $subject instanceof BaseUserInterface;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {

        $admin_user = $token->getUser();
        if (!($admin_user instanceof SonataUser)) { return false; }   

        if ($admin_user->isSuperAdmin() || $admin_user->isFounder()) {
            return true;
        }

        if(!($subject instanceof SonataUser)){ return false ;}

        if ($admin_user->isDirector()) {

            if($attribute === self::PERMISSION_EDIT){
                return !($admin_user->isSuperAdmin()) && !($admin_user->isFounder()) ;
            }

            return $attribute === self::PERMISSION_VIEW ;
        }

        //pour les autres 
        return $this->authorizationCheckerForUser->isGrantedForUser($admin_user, $attribute);
    }
}
