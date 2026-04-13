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

use App\Security\Hash\TokenHasherInterface;
use App\Security\Hash\ValidateTokenHasher;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Implémentation native PHP du hachage sécurisé de tokens.
 * 
 * Utilise les fonctions password_hash/verify de PHP pour garantir :
 * - Résistance aux attaques par force brute (algorithme lent)
 * - Protection contre les rainbow tables (salt automatique)
 * - Sécurité contre les timing attacks (comparaison à temps constant)
 * 
 * Par défaut, utilise Argon2id qui offre le meilleur compromis
 * sécurité/performance actuellement disponible.
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package App\Infrastructure\Security\Hash
 */
final class NativeTokenHasher implements TokenHasherInterface
{
    /**
     * Algorithme de hachage par défaut (Argon2id).
     * Fallback vers bcrypt si indisponible.
     */
    // private const DEFAULT_ALGORITHM = PASSWORD_ARGON2ID;
    private const DEFAULT_ALGORITHM = PASSWORD_DEFAULT;
    /**
     * Algorithme de fallback si Argon2id n'est pas disponible.
     */
    private const FALLBACK_ALGORITHM = PASSWORD_BCRYPT;


    /**
     * Options par défaut pour Argon2id.
     */
    // private const ARGON2_OPTIONS = [
    //     'memory_cost' => 65536,  // 64 MB
    //     'time_cost'   => 4,      // 4 itérations
    //     'threads'     => 2,      // 2 threads parallèles
    // ];
    // Testez avec des options très basses pour voir si ça passe
    private const ARGON2_OPTIONS = [
        'memory_cost' => 16384,  // Réduisez à 16 MB au lieu de 64
        'time_cost'   => 2,      // Réduisez à 2 itérations au lieu de 4
        'threads'     => 1,      // Forcez 1 seul thread
    ];
    /**
     * Options par défaut pour bcrypt.
     */
    private const BCRYPT_OPTIONS = [
        'cost' => 12, // 12 rounds (2^12 = 4096 itérations)
    ];

    private readonly string $algorithm;
    private readonly array $options;

    public function __construct(
        ?string $algorithm = null,
        ?array $options = null,
        private readonly ?LoggerInterface $logger = null
    ) {
        // Déterminer l'algorithme à utiliser
        $this->algorithm = $this->resolveAlgorithm($algorithm);

        // Définir les options selon l'algorithme
        $this->options = $options ?? $this->getDefaultOptions($this->algorithm);
    }

    /**
     * {@inheritdoc}
     */
    public function hash(string $plainToken): string
    {
        //La couche de presentation faire deja la validation
        try {
            $hash = \password_hash($plainToken, PASSWORD_DEFAULT);
           
            if ($hash === false || $hash === null) {
                $this->logger?->error('password_hash a retourné false');
                throw new RuntimeException('Le hachage a échoué.');
            }

            $this->logger?->debug('Token hashé avec succès', [
                'algorithm' => $this->getAlgorithmName($this->algorithm),
                'token_length' => strlen($plainToken),
            ]);

            return $hash;
        } catch (\Exception $e) {
            $this->logger?->error('Échec du hachage de token', [
                'error' => $e->getMessage(),
                'algorithm' => $this->algorithm,
            ]);

            throw new RuntimeException(
                'Impossible de hasher le token de manière sécurisée',
                0,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function verify(string $plainToken, string $hashedToken): bool
    {
        ValidateTokenHasher::validateToken($plainToken) ;
        ValidateTokenHasher::validateHash($hashedToken);

        try {
            $isValid = password_verify($plainToken, $hashedToken);

            $this->logger?->debug('Vérification de token', [
                'valid' => $isValid,
            ]);

            return $isValid;
        } catch (\Exception $e) {
            $this->logger?->error('Échec de la vérification de token', [
                'error' => $e->getMessage(),
            ]);

            // En cas d'erreur, considérer comme invalide pour la sécurité
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function needsRehash(string $hashedToken): bool
    {
        ValidateTokenHasher::validateHash($hashedToken);
        return password_needs_rehash($hashedToken, $this->algorithm, $this->options);
    }

    /**
     * Résout l'algorithme à utiliser avec fallback.
     *
     * @param string|null $algorithm L'algorithme souhaité
     * 
     * @return string L'algorithme sélectionné
     */
    private function resolveAlgorithm(?string $algorithm): string
    {
        // Si un algorithme spécifique est demandé
        if ($algorithm !== null) {
            return $algorithm;
        }

        // Vérifier la disponibilité d'Argon2id
        if (defined('PASSWORD_ARGON2ID')) {
            return self::DEFAULT_ALGORITHM;
        }

        // Fallback vers bcrypt
        $this->logger?->warning('Argon2id non disponible, utilisation de bcrypt');

        return self::FALLBACK_ALGORITHM;
    }

    /**
     * Obtient les options par défaut selon l'algorithme.
     *
     * @param string $algorithm L'algorithme
     * 
     * @return array Les options par défaut
     */
    private function getDefaultOptions(string $algorithm): array
    {
        return match ($algorithm) {
            PASSWORD_ARGON2ID, PASSWORD_ARGON2I => self::ARGON2_OPTIONS,
            PASSWORD_BCRYPT => self::BCRYPT_OPTIONS,
            default => [],
        };
    }


    /**
     * Obtient le nom lisible d'un algorithme.
     *
     * @param string $algorithm L'identifiant de l'algorithme
     * 
     * @return string Le nom lisible
     */
    private function getAlgorithmName(string $algorithm): string
    {
        return match ($algorithm) {
            PASSWORD_ARGON2ID => 'Argon2id',
            PASSWORD_ARGON2I => 'Argon2i',
            PASSWORD_BCRYPT => 'bcrypt',
            default => 'unknown',
        };
    }
}
