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

namespace App\Security\Hash;

use InvalidArgumentException;

final class ValidateTokenHasher 
{
    /**
     * Longueur minimale acceptable pour un token.
     */
    private const MIN_TOKEN_LENGTH = 8;

    /**
     * Valide qu'un token en clair est acceptable.
     *
     * @param string $plainToken Le token à valider
     * 
     * @throws InvalidArgumentException Si le token est invalide
     */
    public static function validateToken(string $plainToken): void
    {
        if (strlen($plainToken) < self::MIN_TOKEN_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le token doit contenir au moins %d caractères. Reçu : %d',
                    self::MIN_TOKEN_LENGTH,
                    strlen($plainToken)
                )
            );
        }
    }

    /**
     * Valide qu'un hash est au bon format.
     *
     * @param string $hashedToken Le hash à valider
     * 
     * @throws InvalidArgumentException Si le hash est invalide
     */
    public static function validateHash(string $hashedToken): void
    {
        if (empty($hashedToken)) {
            throw new InvalidArgumentException('Le hash ne peut pas être vide');
        }

        // Vérifier que c'est bien un hash valide
        if (!str_starts_with($hashedToken, '$')) {
            throw new InvalidArgumentException('Format de hash invalide');
        }
    }
}
