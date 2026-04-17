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

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Listener de rate limiting et protection DDoS.
 *
 * Priorité 900 → s'exécute APRÈS BrowserValidationListener (1000).
 * Les requêtes d'outils automatisés sont donc déjà bloquées avant d'arriver ici.
 *
 * STRATÉGIE DE LIMITATION PAR CONTEXTE :
 * ┌─────────────────────────────┬─────────────────────────────────────────────┐
 * │ Contexte                    │ Limiter                                     │
 * ├─────────────────────────────┼─────────────────────────────────────────────┤
 * │ /login, /register…          │ login_attempts : 5 req / 15 min par IP     │
 * │ /reset-password             │ password_reset : 3 req / 30 min par IP     │
 * │ /api/* (anonyme)            │ api_anonymous  : 60 req / 1 min par IP     │
 * │ /api/* (authentifié)        │ api_authenticated : 300 req / 1 min        │
 * │ Tout le reste               │ global_requests : 120 req / 1 min par IP   │
 * └─────────────────────────────┴─────────────────────────────────────────────┘
 *
 * Requis : symfony/rate-limiter + Redis (voir config/packages/rate_limiter.yaml)
 * Installation : composer require symfony/rate-limiter
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 900)]
final readonly class RateLimitListener
{
    public function __construct(
        private RateLimiterFactory $loginAttemptsLimiter,
        private RateLimiterFactory $passwordResetLimiter,
        private RateLimiterFactory $apiAnonymousLimiter,
        private RateLimiterFactory $apiAuthenticatedLimiter,
        private RateLimiterFactory $globalRequestsLimiter,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Point d'entrée du listener.
     */
    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path    = $request->getPathInfo();

        // Exclure les assets, health checks et webhooks du rate limiting
        if ($this->isExcludedPath($path)) {
            return;
        }

        $ip      = $request->getClientIp() ?? 'unknown';
        $limiter = $this->resolveLimiter($path, $request, $ip);

        if ($limiter === null) {
            return; // Aucun limiter applicable
        }

        $rateLimit = $limiter->consume(1);

        if ($rateLimit->isAccepted()) {
            // Ajouter les headers RateLimit informatifs à la réponse
            return;
        }

        // ── Limite dépassée ──────────────────────────────────────────────────
        $retryAfter = max(
            0,
            $rateLimit->getRetryAfter()->getTimestamp() - time()
        );

        $this->logger?->warning(
            '[RateLimit] Limite dépassée',
            [
                'ip'          => $ip,
                'path'        => $path,
                'method'      => $request->getMethod(),
                'retry_after' => $retryAfter,
                'user_agent'  => $request->headers->get('User-Agent', 'absent'),
                'limiter'     => $this->getLimiterName($path, $request),
            ]
        );

        throw new TooManyRequestsHttpException(
            retryAfter: 10,
            message: sprintf(
                'Trop de requêtes. Veuillez réessayer dans %d seconde%s.',
                $retryAfter,
                $retryAfter > 1 ? 's' : ''
            ),
            headers:[
                'X-RateLimit-Reset'     => (string) $rateLimit->getRetryAfter()->getTimestamp(),
            ]
        );
    }

    // -------------------------------------------------------------------------
    // Résolution du limiter approprié
    // -------------------------------------------------------------------------

    /**
     * Sélectionne le RateLimiterFactory adapté au chemin et au contexte.
     *
     * @return \Symfony\Component\RateLimiter\LimiterInterface|null
     */
    private function resolveLimiter(
        string $path,
        \Symfony\Component\HttpFoundation\Request $request,
        string $ip
    ): ?\Symfony\Component\RateLimiter\LimiterInterface {
        // 1. Routes d'authentification → limiter très strict
        if ($this->isAuthPath($path)) {
            return $this->loginAttemptsLimiter->create('login_' . $ip);
        }

        // 2. Reset de mot de passe → limiter ultra strict
        if (str_starts_with($path, '/reset-password') || str_starts_with($path, '/change-password')) {
            return $this->passwordResetLimiter->create('pwd_reset_' . $ip);
        }

        // 3. Routes API → distinguer authentifié / anonyme
        if (str_starts_with($path, '/api')) {
            $hasAuthHeader = $request->headers->has('Authorization') || $request->headers->has('X-Auth-Token');
            $hasSessionAuth = false;
            if ($request->hasSession()) {
                $session = $request->getSession();
                $hasSessionAuth = $session->isStarted() && $session->has('_security_main');
            }

            $isAuthenticated = $hasAuthHeader || $hasSessionAuth;

            if ($isAuthenticated) {
                // Clé = IP + token partiel pour isoler par utilisateur
                $tokenFragment = substr(
                    $request->headers->get('Authorization', $ip),
                    -16
                );
                return $this->apiAuthenticatedLimiter->create('api_auth_' . $ip . '_' . $tokenFragment);
            }

            return $this->apiAnonymousLimiter->create('api_anon_' . $ip);
        }

        // 4. Toutes les autres routes → limiter global
        return $this->globalRequestsLimiter->create('global_' . $ip);
    }

    /**
     * Retourne le nom lisible du limiter pour les logs.
     */
    private function getLimiterName(
        string $path,
        \Symfony\Component\HttpFoundation\Request $request
    ): string {
        if ($this->isAuthPath($path)) {
            return 'login_attempts';
        }
        if (str_starts_with($path, '/reset-password') || str_starts_with($path, '/change-password')) {
            return 'password_reset';
        }
        if (str_starts_with($path, '/api')) {
            $isAuthenticated = $request->headers->has('Authorization')
                || $request->headers->has('X-Auth-Token');
            return $isAuthenticated ? 'api_authenticated' : 'api_anonymous';
        }

        return 'global_requests';
    }

    /**
     * Routes d'authentification / inscription.
     */
    private function isAuthPath(string $path): bool
    {
        $authPaths = ['/login', '/register', '/logout', '/2fa'];

        foreach ($authPaths as $authPath) {
            if (str_starts_with($path, $authPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Chemins exemptés du rate limiting (assets, health, callbacks…).
     */
    private function isExcludedPath(string $path): bool
    {
        $excluded = [
            '/health',
            '/ping',
            '/webhook',
            '/payment/callback',
            '/bundles/',
            '/assets/',
            '/build/',
            '/_profiler',
            '/_wdt',
            '/.well-known/',
            '/favicon.ico',
            '/robots.txt',
            '/sitemap.xml',
        ];

        foreach ($excluded as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
