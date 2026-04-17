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

use App\Entity\SonataUser;
use App\Entity\TeamMember;
use App\Security\Authorization\AuthorizationCheckerForUser;
use App\Security\Voter\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
final class TeamMemberVoter extends Voter implements Permission
{
    public function __construct(
        private readonly AuthorizationCheckerForUser $authorizationCheckerForUser
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {

        return in_array($attribute, self::ADMIN_USER_PERMISSION)
            && $subject instanceof TeamMember;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {

        $admin_user = $token->getUser();
        if (!($admin_user instanceof SonataUser)) {
            return false;
        }

        if (!($subject instanceof TeamMember)) {
            return false;
        }
       
        // Le SuperAdmin et le Fondateur ont tous les droits par défaut
        // SAUF si on veut protéger spécifiquement l'ID 1 même contre eux.
        if ($admin_user->isSuperAdmin() || $admin_user->isFounder()) {
            // Optionnel : Empêcher même le SuperAdmin de supprimer l'ID 1
            if ($attribute === self::PERMISSION_DELETE && (int) $subject->getId() === 1) {
                return false;
            }
            return true;
        }
        
        // PROTECTION CRITIQUE : Personne d'autre ne touche à l'ID 1
        if ((int) $subject->getId() === 1) {
            return false;
        }
    
        //pour les autres 
        return $this->authorizationCheckerForUser->isGrantedForUser($admin_user, $attribute);
    }
}
