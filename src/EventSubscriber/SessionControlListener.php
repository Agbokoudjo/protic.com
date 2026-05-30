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

namespace App\EventSubscriber;

use App\Domain\BaseUserInterface;
use App\Persistance\UserSessionManagerInterface;
use App\Queue\AsyncMethodDispatcherInterface;
use App\Security\Encryption\IdEncryptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Service\DeviceFingerprintUserAgent ;

/**
 * Gardien des sessions uniques — vérifie à chaque requête HTTP que
 * l'utilisateur n'est pas connecté simultanément sur un autre appareil.
 *
 * Fonctionnement :
 *  1. Se déclenche sur KernelEvents::REQUEST (APRÈS le firewall)
 *  2. Filtre les routes Sonata uniquement (préfixe "sonata_")
 *  3. Compare l'ID de session courant avec celui enregistré en base
 *  4. Conflit détecté → redirection (ou JSON 401 pour XHR)
 *  5. Pas de conflit  → mise à jour asynchrone de l'activité
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
#[AsEventListener(
    event: KernelEvents::REQUEST,
    /*
     * Priorité 7 = APRÈS le firewall (priority 8).
     * Le token de sécurité est déjà peuplé quand ce listener s'exécute.
     * Ne pas augmenter au-dessus de 8 sinon getToken() retourne toujours null.
     */
    priority: 7,
)]
final readonly class SessionControlListener
{
    use DeviceFingerprintUserAgent ;
    
    /**
     * Routes Sonata qui ne doivent PAS être vérifiées
     * (connexion, déconnexion, redirections post-login).
     */
    private const IGNORED_SONATA_ROUTES = [
        'sonata_user_admin_security_login',
        'sonata_user_admin_security_check',
        'sonata_user_admin_security_logout',
        'sonata_admin_redirect',
        'app_admin_user_login'
    ];

    public function __construct(
        private UserSessionManagerInterface $sessionManager,
        private AsyncMethodDispatcherInterface $asyncMethodDispatcher,
        private RouterInterface $router,
        private TokenStorageInterface $tokenStorage,
        private LoggerInterface $logger,
        private IdEncryptionInterface $idEncryptionService
    ) {}

     public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        try {
            $request = $event->getRequest();
            $route   = $request->attributes->get('_route');

            if (!str_starts_with($route ?? '', 'sonata_')
                && $route !== 'app_admin_user_login'
            ) {
                return;
            }

            if (in_array($route, self::IGNORED_SONATA_ROUTES, true)) {
                return;
            }

            $token = $this->tokenStorage->getToken();
            if ($token === null) {
                return;
            }

            $user = $token->getUser();
            if (!$user instanceof BaseUserInterface) {
                return;
            }

            $session          = $request->getSession();
            $currentSessionId = $session->getId();

            // ÉTAPE 1 — Traiter le flag de session post-login
            // À ce stade, session_fixation_strategy a déjà régénéré le
            // sessionId. $currentSessionId est le sessionId FINAL.
            $pendingData = $session->get(LoginSubscriber::PENDING_SESSION_REGISTRATION);

            if ($pendingData !== null) {
                // Effacer le flag immédiatement (une seule exécution)
                $session->remove(LoginSubscriber::PENDING_SESSION_REGISTRATION);

                // Vérifier si une session active existe déjà AVANT de créer
                $existingSession = $this->sessionManager->findActiveSession(
                    $pendingData['userIdentifier']
                );

                if ($existingSession !== null
                    && $existingSession->getSessionId() !== $currentSessionId
                ) {
                    // ── Conflit détecté au login → bloquer ce navigateur ──
                    $this->logger->warning(
                        'Double connexion bloquée — session active existante',
                        [
                            'user'                => $pendingData['userIdentifier'],
                            'existing_session_id' => $existingSession->getSessionId(),
                            'blocked_session_id'  => $currentSessionId,
                            'ip'                  => $request->getClientIp(),
                        ]
                    );
                    $event->setResponse(
                        new RedirectResponse(
                            $this->router->generate('app_session_conflict', [
                                'id' => $this->idEncryptionService->encryptId(
                                    $user->getId()
                                ),
                            ])
                        )
                    );

                    $event->stopPropagation();
                    return;
                }

                // ── Pas de conflit : enregistrer la session avec l'ID final ──
                $this->sessionManager->createSession(
                    $pendingData['userIdentifier'],
                    $currentSessionId,        
                    $pendingData['ipAddress'],
                    $pendingData['userAgent'],
                    $pendingData['deviceFingerprint'],
                );

                $this->logger->info('Session enregistrée en BDD (post-migration)', [
                    'user'       => $pendingData['userIdentifier'],
                    'session_id' => $currentSessionId,
                ]);

                return; // Première requête traitée, on arrête là
            }

             // ── ÉTAPE 2 : remember-me ou session restaurée sans flag ──────────
            // Vérifier si ce sessionId existe en BDD comme session active.
            // S'il n'existe pas → remember-me a restauré l'utilisateur mais
            // aucune session n'a été créée en BDD (après logout + remember-me).
            $existingActive = $this->sessionManager->findActiveSession(
                $user->getUserIdentifier()
            );

            if ($existingActive === null) {
                // Aucune session active en BDD → créer silencieusement
                // (cas remember-me après logout propre)
                $this->sessionManager->createSession(
                    $user->getUserIdentifier(),
                    $currentSessionId,
                    $request->getClientIp(),
                    $request->headers->get('User-Agent'),
                    $this->buildDeviceFingerprint($request),
                );

                $this->logger->info('Session créée en BDD (remember-me / restauration)', [
                    'user'       => $user->getUserIdentifier(),
                    'session_id' => $currentSessionId,
                ]);

                return;
            }

            // Session active existe — est-ce la bonne ?
            if ($existingActive->getSessionId() === $currentSessionId) {
                // Même sessionId → pas de conflit, mise à jour activité
                $this->asyncMethodDispatcher->dispatch(
                    UserSessionManagerInterface::class,
                    'updateSessionActivity',
                    [$currentSessionId]
                );
                return;
            }

            // ── ÉTAPE 3 : conflit — session active différente ─────────────────
            $this->logger->warning('Conflit de session détecté', [
                'user'            => $user->getUserIdentifier(),
                'current_session' => $currentSessionId,
                'active_session'  => $existingActive->getSessionId(),
                'route'           => $route,
            ]);

            $response = $request->isXmlHttpRequest()
                ? new JsonResponse([
                    'error'   => 'session_conflict',
                    'message' => 'Your session was closed because you signed in on another device.',
                ], JsonResponse::HTTP_UNAUTHORIZED)
                : new RedirectResponse(
                    $this->router->generate('app_session_conflict', [
                        'id' => $this->idEncryptionService->encryptId($user->getId()),
                    ])
                );

            $event->setResponse($response);
            $event->stopPropagation();

        } catch (\Throwable $throwable) {
            $this->logger->error('Erreur SessionControlListener', [
                'exception_message' => $throwable->getMessage(),
                'file'              => $throwable->getFile(),
                'line'              => $throwable->getLine(),
                'route'             => $event->getRequest()->attributes->get('_route'),
            ]);
        }
    }

    /**
     * Récupère l'identifiant utilisateur de manière sécurisée
     * (pour les logs dans le bloc catch).
     */
    private function getUserIdentifierSafely(): string
    {
        try {
            $user = $this->tokenStorage->getToken()?->getUser();

            return ($user instanceof BaseUserInterface)
                ? $user->getUserIdentifier()
                : 'unknown';
        } catch (\Throwable) {
            return 'unavailable';
        }
    }

    /**
     * Récupère l'ID de session de manière sécurisée
     * (pour les logs dans le bloc catch).
     */
    private function getSessionIdSafely(RequestEvent $event): string
    {
        try {
            return $event->getRequest()->getSession()->getId();
        } catch (\Throwable) {
            return 'unavailable';
        }
    }
}
