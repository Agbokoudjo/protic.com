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

namespace App\Security\Generator;

/**
 * Interface pour la génération de tokens cryptographiquement sécurisés.
 * 
 * Définit le contrat pour générer des tokens aléatoires utilisés pour :
 * - Confirmation d'email
 * - Réinitialisation de mot de passe
 * - Vérification 2FA
 * - Tokens CSRF
 * - Tokens API
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package App\Domain\User\Service\Security
 */
interface TokenGeneratorInterface
{
    /**
     * Longueur par défaut recommandée pour un token de confirmation d'email.
     */
    public const DEFAULT_EMAIL_TOKEN_LENGTH = 32;

    /**
     * Longueur par défaut pour un token de réinitialisation de mot de passe.
     */
    public const DEFAULT_PASSWORD_RESET_TOKEN_LENGTH = 32;

    /**
     * Longueur par défaut pour un token API.
     */
    public const DEFAULT_API_TOKEN_LENGTH = 64;

    /**
     * Génère un token aléatoire cryptographiquement sécurisé.
     * 
     * Le token généré utilise des caractères hexadécimaux (0-9, a-f)
     * pour garantir la compatibilité URL et base de données.
     *
     * @param int $length La longueur souhaitée du token en caractères (minimum 1)
     * 
     * @return string Le token généré en hexadécimal
     * 
     * @throws \InvalidArgumentException Si la longueur est invalide
     * @throws \Exception Si la génération cryptographique échoue
     */
    public function generate(int $length = self::DEFAULT_EMAIL_TOKEN_LENGTH): string;
}
