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

namespace App\Service;

use App\Exception\EmailAlreadyVerifiedException;
use App\Exception\InvalidTokenException;
use App\Persistance\UserManagerInterface;
use App\QueueHandler\AsyncMethodDispatcher;
use App\Security\EmailVerificationInterface;
use App\Security\Hash\TokenHasherInterface;
use App\Service\ApplyEmailVerificationService;
use App\Service\SecureTokenService;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Service de vérification des emails utilisateurs.
 * 
 * Gère la vérification des tokens de confirmation d'email envoyés aux utilisateurs
 * lors de leur inscription. Vérifie l'expiration et la validité du token avant
 * d'activer le compte.
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package App\Infrastructure\Service
 */
final class EmailVerificationService implements EmailVerificationInterface
{
    public function __construct(
        private readonly UserManagerInterface $userManager,
        private readonly TokenHasherInterface $tokenHasher,
        private readonly SecureTokenService $secureTokenService,
        private readonly AsyncMethodDispatcher $asyncMethodDispatcher
    ) {}

    /**
     * {@inheritdoc}
     * 
     * @throws InvalidTokenException Si le token est invalide, expiré ou déjà utilisé
     */
    public function verifyEmail(string $rawToken, string $slug): void
    {
        $this->asyncMethodDispatcher->dispatch(
            LoggerInterface::class,
            'info',
            [
                'Tentative de vérification d\'email',
                [
                    'slug' => $slug
                ]
            ]
        ) ;

        $user = $this->userManager->findUserBySlug($slug);

        if ($user === null) {
            $this->asyncMethodDispatcher->dispatch(
                LoggerInterface::class,
                'warning',
                [
                    'Utilisateur non trouvé pour la vérification d\'email',
                    [
                        'slug' => $slug
                    ]
                ]
            );

            throw InvalidTokenException::userNotFound();
        }

        // 2. Récupération des données de token
        $hashedToken = $user->getConfirmationToken();
        $requestedAt = $user->getTokenRequestedAt();

        // 3. Vérification que le token existe
        if ($hashedToken === null || $requestedAt === null) {
            $this->asyncMethodDispatcher->dispatch(
                LoggerInterface::class,
                'info',
                [
                    'Token manquant ou compte déjà vérifié',
                    [
                        'user_id' => $user->getId(),
                        'has_token' => $hashedToken !== null,
                        'has_requested_at' => $requestedAt !== null,
                    ]
                ]
            );

            throw InvalidTokenException::alreadyUsedToken(
                $user->getEmailVerifiedAt() ?? new \DateTimeImmutable()
            );
        }

        //  Vérification de l'expiration du token
        if ($this->isTokenExpired($requestedAt)) {
            $expiresAt = $this->calculateExpirationDate($requestedAt);
            $this->asyncMethodDispatcher->dispatch(
                LoggerInterface::class,
                'warning',
                [
                    'Token de vérification expiré',
                    [
                        'user_id' => $user->getId(),
                        'requested_at' => $requestedAt->format('Y-m-d H:i:s'),
                        'expires_at' => $expiresAt->format('Y-m-d H:i:s')
                    ]
                ]
            );

            throw InvalidTokenException::expiredToken($expiresAt);
        }

        // Vérification de la validité du token (comparaison hash)
        if (!$this->tokenHasher->verify($rawToken, $hashedToken)) {
            $this->asyncMethodDispatcher->dispatch(
                LoggerInterface::class,
                'error',
                [
                    'Token de vérification invalide (hash mismatch)',
                    [
                        'user_id' => $user->getId(),
                        'slug' => $slug,
                    ]
                ]
            );

            throw InvalidTokenException::invalidToken('Hash mismatch');
        }

        //  Vérifier si rehash nécessaire
        try {
            if ($this->tokenHasher->needsRehash($hashedToken)) {
                $this->asyncMethodDispatcher->dispatch(
                    LoggerInterface::class,
                    'info',
                    [
                        'Rehashing token avec paramètres plus récents'
                    ]
                );
            }
        } catch (InvalidArgumentException $e) {
            throw $e;
        }

        // 7. Application des changements (logique métier) en asynchrone
        $this->asyncMethodDispatcher->dispatch(
            ApplyEmailVerificationService::class,
            'handle',
            [$user,$this->userManager]
        );

        $this->asyncMethodDispatcher->dispatch(
            LoggerInterface::class,
            'info',
            [
                'Email vérifié avec succès',
                [
                    'user_id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'auto_enabled' => $user->getStatus(),
                ]
            ]
        );
        
    }

   
    /**
     * Vérifie si un token a expiré.
     *
     * @param \DateTimeInterface $requestedAt Date de création du token
     * 
     * @return bool True si expiré, false sinon
     */
    private function isTokenExpired(\DateTimeInterface $requestedAt): bool
    {
        $now = new \DateTimeImmutable();
        $expirationTimestamp = $requestedAt->getTimestamp() + self::TOKEN_LIFETIME;

        return $now->getTimestamp() > $expirationTimestamp;
    }

    /**
     * Calcule la date d'expiration d'un token.
     *
     * @param \DateTimeInterface $requestedAt Date de création du token
     * 
     * @return \DateTimeImmutable Date d'expiration
     */
    private function calculateExpirationDate(\DateTimeInterface $requestedAt): \DateTimeImmutable
    {
        $expirationTimestamp = $requestedAt->getTimestamp() + self::TOKEN_LIFETIME;

        return (new \DateTimeImmutable())->setTimestamp($expirationTimestamp);
    }

    public function resendVerificationEmail(string $slug): void
    {
        $this->asyncMethodDispatcher->dispatch(
            LoggerInterface::class,
            'info',
            [
                'Demande de renvoi d\'email de vérification',
                [
                    'slug' => $slug
                ]
            ]
        );

        // 1. Récupération de l'utilisateur
        $user = $this->userManager->findUserBySlug($slug);

        if ($user === null) {
            $this->asyncMethodDispatcher->dispatch(
                LoggerInterface::class,
                'warning',
                [
                    'Utilisateur non trouvé pour renvoi de vérification',
                    [
                        'slug' => $slug
                    ]
                ]
            );

            throw InvalidTokenException::userNotFound();
        }

        // 2. Vérification si l'email est déjà vérifié
        if ($user->isEmailVerified()) {
            $this->asyncMethodDispatcher->dispatch(
                LoggerInterface::class,
                'info',
                [
                    'Tentative de renvoi pour email déjà vérifié',
                    [
                        'user_id' => $user->getId(),
                        'email' => $user->getEmail(),
                    ]
                ]
            );

            throw EmailAlreadyVerifiedException::forUser($user->getEmail());
        }

        try {
            // Le service s'occupe de tout : cooldown, génération, hachage, persistance et dispatch de l'événement.
            $this->secureTokenService->generateEmailConfirmationToken($user, $this->userManager);

            $this->asyncMethodDispatcher->dispatch(
                LoggerInterface::class,
                'info',
                [
                    'Email de vérification renvoyé avec succès',
                    [
                        'user_id' => $user->getId(),
                        'email' => $user->getEmail(),
                    ]
                ]
            );

        } catch (\RuntimeException $e) {
            // Le SecureTokenService lève RuntimeException pour le Cool-down.
            // Nous la relançons pour informer l'utilisateur de l'attente.
            $this->asyncMethodDispatcher->dispatch(
                LoggerInterface::class,
                'notice',
                [
                    'Renvoi de token bloqué par le cooldown',
                    [
                        'user_id' => $user->getId(),
                        'message' => $e->getMessage(),
                    ]
                ]
            );

            throw $e;
        } catch (\Exception $e) {
            // Log toutes les autres erreurs imprévues (génération, hachage, persistance)
            $this->asyncMethodDispatcher->dispatch(
                LoggerInterface::class,
                'error',
                [
                    'Échec critique du renvoi d\'email de vérification',
                    [
                        'user_id' => $user->getId(),
                        'error' => $e->getMessage(),
                    ]
                ]
            );
           
            // Relancer une RuntimeException générique pour l'utilisateur
            throw new \RuntimeException(
                'Impossible de renvoyer l\'email de vérification. Veuillez réessayer plus tard.',
                0,
                $e
            );
        }
    }
}
