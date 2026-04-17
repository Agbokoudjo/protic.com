<?php

declare(strict_types=1);

/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * Phone: +229 01 67 25 18 86
 * Company: INTERNATIONALES WEB APPS & SERVICES
 */

namespace App\Security\Voter;

use App\Entity\SonataUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * SonataUserVoter
 *
 * Attributs gérés :
 *   - EDIT_PASSWORD : accordé uniquement si l'utilisateur courant est le
 *                     même que le sujet soumis à l'Admin (édition de son
 *                     propre compte). Tout autre utilisateur, même un
 *                     Super Admin, se voit refuser cette autorisation.
 *
 *   - EDIT_SELF     : accordé si l'utilisateur courant édite son propre compte.
 *                     Utilisé pour verrouiller certains champs sensibles.
 *
 * Usage dans l'Admin :
 *   $this->isGranted(SonataUserVoter::EDIT_PASSWORD, $subject)
 *
 * Enregistrement automatique via autoconfigure (tag security.voter).
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 *
 * @extends Voter<string, SonataUser>
 */
final class PasswordUserVoter extends Voter
{
    /**
     * Autorise la modification du mot de passe uniquement si l'utilisateur
     * courant est le propriétaire du compte édité.
     */
    public const EDIT_PASSWORD = 'sonata_user_edit_password';

    /**
     * Autorise l'édition de son propre profil.
     */
    public const EDIT_SELF     = 'sonata_user_edit_self';

    /**
     * Ce Voter s'applique uniquement aux attributs définis ci-dessus
     * et uniquement quand le sujet est une instance de SonataUser.
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::EDIT_PASSWORD, self::EDIT_SELF], true)
            && $subject instanceof SonataUser;
    }

    /**
     * @param SonataUser $subject L'entité SonataUser en cours d'édition
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();

        // Si l'utilisateur n'est pas authentifié → refus systématique
        if (!$currentUser instanceof SonataUser) {
            return false;
        }

        return match ($attribute) {
            self::EDIT_PASSWORD => $this->canEditPassword($currentUser, $subject),
            self::EDIT_SELF     => $this->isSelf($currentUser, $subject),
            default             => false,
        };
    }

    /**
     * L'utilisateur courant peut modifier le mot de passe SI ET SEULEMENT SI
     * il édite son propre compte.
     *
     * Même un ROLE_SUPER_ADMIN ne peut pas forcer un nouveau mot de passe via
     * ce formulaire — il devrait passer par un flux dédié (reset par token, etc.)
     */
    private function canEditPassword(SonataUser $currentUser, SonataUser $subject): bool
    {
        return $this->isSelf($currentUser, $subject);
    }

    /**
     * Détermine si l'utilisateur courant est le même que le sujet.
     * Comparaison par identifiant de base de données (int|string).
     */
    private function isSelf(SonataUser $currentUser, SonataUser $subject): bool
    {
        // L'id peut être null si le sujet n'est pas encore persisté
        if (null === $subject->getId() || null === $currentUser->getId()) {
            return false;
        }

        return (string) $currentUser->getId() === (string) $subject->getId();
    }
}
