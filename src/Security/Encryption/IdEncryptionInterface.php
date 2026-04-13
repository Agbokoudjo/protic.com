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

namespace App\Security\Encryption;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Service pour chiffrer et déchiffrer les IDs des entités
 * 
 * Utilise sodium (libsodium) pour un chiffrement symétrique sécurisé
 * 
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
interface IdEncryptionInterface
{
    /**
     * Chiffre un ID en Base64 URL-SAFE
     * 
     * ✅ Pas de "/" ou "+" pour éviter les conflits avec les routes
     * 
     * @param int|string $id L'ID à chiffrer
     * @return string L'ID chiffré en Base64 URL-SAFE
     */
    public function encryptId(int|string $id): string ;

    /**
     * Déchiffre un ID
     * 
     * @param string $encryptedId L'ID chiffré et encodé en base64
     * @return int|string L'ID original
     * 
     * @throws BadRequestHttpException Si le déchiffrement échoue
     */
    public function decryptId(string $encryptedId): int|string ;

    /**
     * Génère une clé de chiffrement
     * 
     * À utiliser une seule fois pour générer la clé à stocker en .env
     * 
     * @return string Clé générée (base64)
     */
    public static function generateEncryptionKey(): string;
}
