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

use App\Queue\AsyncMethodDispatcherInterface;
use App\Security\Generator\TokenGeneratorInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Générateur de tokens cryptographiquement sécurisés.
 * 
 * Utilise random_bytes() pour garantir l'imprévisibilité des tokens générés.
 * Les tokens sont convertis en hexadécimal pour assurer la compatibilité
 * avec les URLs et les bases de données.
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package App\Infrastructure\Security
 */
final class RandomTokenGenerator implements TokenGeneratorInterface
{
    /**
     * Longueur minimale autorisée pour un token (8 caractères = 4 octets).
     */
    private const MIN_LENGTH = 8;

    /**
     * Longueur maximale autorisée pour un token (256 caractères = 128 octets).
     */
    private const MAX_LENGTH = 256;

    public function __construct(
        private readonly AsyncMethodDispatcherInterface $asyncMethodDispatcher
    ) {}

    /**
     * {@inheritdoc}
     */
    public function generate(int $length = self::DEFAULT_EMAIL_TOKEN_LENGTH): string
    {
        $this->validateLength($length);

        try {
            // 1. Calculer le nombre d'octets nécessaires
            // bin2hex() double la longueur : 1 octet = 2 caractères hex
            $bytesNeeded = (int) ceil($length / 2);

            // 2. Générer des octets aléatoires cryptographiquement sécurisés
            $randomBytes = random_bytes($bytesNeeded);

            // 3. Convertir en hexadécimal
            $token = bin2hex($randomBytes);

            // 4. Tronquer à la longueur exacte demandée
            return substr($token, 0, $length);
        } catch (\Exception $e) {
            // Log critique : le système d'aléatoire est compromis
            $this->asyncMethodDispatcher->dispatch(
                LoggerInterface::class,
                'critical',
                [
                    'Échec de la génération de token cryptographique',
                    [
                        'error' => $e->getMessage(),
                        'requested_length' => $length,
                    ]
                ]
            ) ;

            throw new \RuntimeException(
                'Impossible de générer un token sécurisé. Le générateur d\'aléa système est indisponible.',
                0,
                $e
            );
        }
    }

    /**
     * Valide que la longueur demandée est dans les limites acceptables.
     *
     * @param int $length La longueur à valider
     * 
     * @throws InvalidArgumentException Si la longueur est invalide
     */
    private function validateLength(int $length): void
    {
        if ($length < self::MIN_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'La longueur du token doit être d\'au moins %d caractères. Reçu : %d',
                    self::MIN_LENGTH,
                    $length
                )
            );
        }

        if ($length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'La longueur du token ne peut pas dépasser %d caractères. Reçu : %d',
                    self::MAX_LENGTH,
                    $length
                )
            );
        }
    }
}
