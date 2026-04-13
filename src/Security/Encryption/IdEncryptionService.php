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

use App\Security\Encryption\IdEncryptionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
final class IdEncryptionService implements IdEncryptionInterface
{
    /**
     * Clé de chiffrement
     */
    private readonly string $encryptionKey;

    public function __construct(string $encryptionKey)
    {
        // Décoder depuis Base64 d'abord
        $decodedKey = base64_decode($encryptionKey, strict: true);

        if ($decodedKey === false) {
            throw new \InvalidArgumentException(
                'La clé de chiffrement doit être encodée en Base64 valide'
            );
        }

        // Valider que la clé fait 32 bytes (256 bits)
        if (strlen($decodedKey) !== 32) {
            throw new \InvalidArgumentException(
                sprintf(
                    'La clé de chiffrement doit avoir exactement 32 bytes (256 bits) après décodage Base64, '
                        . 'mais vous avez fourni %d bytes',
                    strlen($decodedKey)
                )
            );
        }

        $this->encryptionKey = $decodedKey;
    }

    public function encryptId(int|string $id): string
    {
        try {
            // Générer un nonce aléatoire
            $nonce = random_bytes(\SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            
            // Chiffrer l'ID
            $plaintext = (string) $id;
            $ciphertext = \sodium_crypto_secretbox(
                $plaintext, //Le message en clair à chiffrer.
                $nonce, //
                $this->encryptionKey //La clé de chiffrement (256 bits).
            ); //Chiffrement authentifié avec une clé partagée

            // Combiner nonce + ciphertext et encoder en base64
            $encrypted = $nonce . $ciphertext;

            // Encoder en Base64 URL-SAFE
            $encoded = $this->base64UrlEncode($encrypted);
            
            return $encoded;
        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf('Erreur lors du chiffrement de l\'ID: %s', $e->getMessage())
            );
        }
    }

    public function decryptId(string $encryptedId): int|string
    {
        try {
            //  Décoder depuis Base64 URL-SAFE
            $encrypted = $this->base64UrlDecode($encryptedId);
            
            if ($encrypted === false) {
                throw new BadRequestHttpException('ID invalide (décodage base64 échoué)');
            }
            
            // Extraire le nonce et le ciphertext
            $nonceSize = \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
            $nonce = substr($encrypted, 0, $nonceSize);
            $ciphertext = substr($encrypted, $nonceSize);
            
            if (strlen($nonce) !== $nonceSize) {
                throw new BadRequestHttpException('ID invalide (nonce incorrect)');
            }
            
            // Déchiffrer
            $plaintext = \sodium_crypto_secretbox_open(
                $ciphertext,
                $nonce,
                $this->encryptionKey
            );
            
            if ($plaintext === false) {
                throw new BadRequestHttpException('ID invalide (déchiffrement échoué)');
            }
            
            // Retourner comme entier si possible
            return is_numeric($plaintext) ? (int) $plaintext : $plaintext;
        } catch (BadRequestHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new BadRequestHttpException(
                sprintf('Erreur lors du déchiffrement de l\'ID: %s', $e->getMessage())
            );
        }
    }

    /**
     * Encode en Base64 URL-SAFE
     * 
     * Remplace :
     * - "+" par "-"
     * - "/" par "_"
     * - "=" par "" (padding)
     */
    private function base64UrlEncode(string $data): string
    {
        $encoded = base64_encode($data);
        // Remplacer les caractères non-URL-safe
        $encoded = strtr($encoded, '+/', '-_');
        // Enlever le padding
        $encoded = rtrim($encoded, '=');
        return $encoded;
    }

    /**
     * Décode depuis Base64 URL-SAFE
     */
    private function base64UrlDecode(string $data): string|false
    {
        // Ajouter le padding si nécessaire
        $padding = 4 - (strlen($data) % 4);
        if ($padding !== 4) {
            $data .= str_repeat('=', $padding);
        }
        // Restaurer les caractères Base64 standard
        $data = strtr($data, '-_', '+/');

        return base64_decode($data, strict: true);
    }

    public static function generateEncryptionKey(): string
    {
        $key = random_bytes(\SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        return base64_encode($key);
    }
}