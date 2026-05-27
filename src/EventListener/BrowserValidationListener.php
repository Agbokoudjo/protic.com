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

use App\Security\BrowserRequestValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listener Symfony qui intercepte toutes les requêtes HTTP et délègue
 * la validation navigateur à BrowserRequestValidator.
 *
 * Priorité 1000 → s'exécute en premier, avant le RateLimitListener (900).
 *
 * CORRECTIONS v2 :
 * - Utilisation de getBlockReasonCode() dans les logs (raison détaillée)
 * - getBlockReasonMessage() utilisé uniquement pour la réponse client
 * - shouldValidatePath() enrichi (routes Sonata Admin, webhook Stripe, etc.)
 * - Log enrichi avec le code raison
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 1000)]
final readonly class BrowserValidationListener
{
    public function __construct(
        private BrowserRequestValidator $validator,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Point d'entrée du listener.
     */
    public function __invoke(RequestEvent $event): void
    {
        // Ignorer les sous-requêtes (forward, render ESI, etc.)
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path    = $request->getPathInfo();

        // N'appliquer la validation qu'aux routes sensibles
        if (!$this->shouldValidatePath($path)) {
            return;
        }

        if ($this->validator->isValidBrowserRequest($request)) {
            return; // Requête valide → on laisse passer
        }

        // --- Requête bloquée ---
        // Message générique pour le client (ne révèle rien)
        $clientMessage = $this->validator->getBlockReasonMessage($request);

        // Code détaillé réservé aux logs internes
        $reasonCode = $this->validator->getBlockReasonCode($request);

        $event->setResponse(
            new JsonResponse(
                [
                    'status'    => 'error',
                    'message'   => $clientMessage,
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                ],
                Response::HTTP_NOT_EXTENDED
            )
        );

        $this->logBlockedRequest($request, $clientMessage, $reasonCode);
    }

    /**
     * Détermine si le chemin doit être soumis à la validation navigateur.
     *
     * STRATÉGIE :
     * 1. Exclure d'abord les chemins exemptés (assets, callbacks, health, etc.)
     * 2. Inclure ensuite les chemins sensibles (API, admin, auth, etc.)
     * 3. Par défaut : ne pas valider (permissif sur les pages publiques)
     */
    private function shouldValidatePath(string $path): bool
    {
        // ── Chemins toujours exemptés (webhooks, assets, health checks…) ──────
        $excludedPrefixes = [
            '/health',
            '/ping',
            '/webhook',                  // Webhooks génériques
            '/payment/callback',         // Callbacks PSP (Stripe, PayDunya…)
            '/bundles/',                 // Assets Symfony
            '/assets/',
            '/build/',                   // Vite/Webpack build output
            '/_profiler',                // Symfony Profiler (dev uniquement)
            '/_wdt',                     // Web Debug Toolbar
            '/.well-known/',             // ACME / SSL / BIMI
            '/favicon.ico',
            '/robots.txt',
            '/sitemap.xml',
        ];

        foreach ($excludedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return false;
            }
        }

        // ── Chemins sensibles à valider ──────────────────────────────────────
        $sensitivePrefixes = [
            '/api/',          // API Platform
            '/api',           // ex : GET /api (entrypoint)
            '/admin',         // Sonata Admin / EasyAdmin
            '/dashboard',
            '/user',
            '/profile',
            '/account',
            '/login',
            '/form/login',
            '/logout',
            '/register',
            '/reset-password',
            '/change-password',
            '/2fa',
            '/toggle_enabled_user_account',
        ];

        foreach ($sensitivePrefixes as $prefix) {
            if (str_starts_with($path, $prefix) || $path === $prefix) {
                return true;
            }
        }

        // Par défaut : ne pas valider les pages publiques (accueil, blog…)
        return false;
    }

    /**
     * Enregistre la requête bloquée avec tous les détails utiles pour l'investigation.
     */
    private function logBlockedRequest(
        \Symfony\Component\HttpFoundation\Request $request,
        string $clientMessage,
        string $reasonCode
    ): void {
        $this->logger?->warning(
            '[BrowserValidation] Requête bloquée',
            [
                'reason_code'    => $reasonCode,
                'reason_message' => $clientMessage,
                'ip'             => $request->getClientIp(),
                'method'         => $request->getMethod(),
                'path'           => $request->getPathInfo(),
                'user_agent'     => $request->headers->get('User-Agent', 'absent'),
                'accept'         => $request->headers->get('Accept', 'absent'),
                'accept_lang'    => $request->headers->get('Accept-Language', 'absent'),
                'accept_enc'     => $request->headers->get('Accept-Encoding', 'absent'),
                'referer'        => $request->headers->get('Referer', 'absent'),
                'origin'         => $request->headers->get('Origin', 'absent'),
                'sec_fetch_site' => $request->headers->get('Sec-Fetch-Site', 'absent'),
                'sec_fetch_mode' => $request->headers->get('Sec-Fetch-Mode', 'absent'),
                'sec_fetch_dest' => $request->headers->get('Sec-Fetch-Dest', 'absent'),
                'x_forwarded'    => $request->headers->get('X-Forwarded-For', 'absent'),
                'protocol'       => $request->getProtocolVersion(),
                'cookies_count'  => $request->cookies->count(),
            ]
        );
    }
}
