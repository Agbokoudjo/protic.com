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

use App\Entity\BaseUserInterface;
use App\Queue\AsyncMethodDispatcherInterface;
use App\Security\Provider\UserProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Subscriber pour l'enregistrement des déconnexions utilisateur.
 * 
 * Responsabilités :
 * - Logger toutes les déconnexions (volontaires ou par expiration de session)
 * - Calculer et enregistrer la durée de la session
 * - Capturer le contexte de déconnexion (IP, user agent, etc.)
 * - Détecter les déconnexions anormales (sessions très courtes)
 * 
 * Le traitement est effectué de manière asynchrone pour ne pas
 * impacter les performances de la déconnexion.
 *
 * @internal Ce subscriber est appelé automatiquement par Symfony Security
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package App\UI\Http\EventSubscriber
 */
final class LogoutSubscriber implements EventSubscriberInterface
{
    /**
     * Durée minimale de session considérée comme normale (en secondes).
     * Une session plus courte peut indiquer un problème technique ou de sécurité.
     */
    private const MIN_NORMAL_SESSION_DURATION = 30;

    public function __construct(
        private readonly AsyncMethodDispatcherInterface $asyncDispatcher,
        private readonly ParameterBagInterface $parameterBag,
        private UserProvider $userProvider,
        private readonly ?LoggerInterface $logger = null
    ) {}

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => [
                ['onLogout', 100],
                ['onSession', -101],
                ['onRemoveUserCache', 102]
            ]
        ];
    }

    /**
     * Enregistre les déconnexions utilisateur avec contexte complet.
     * 
     * Capture les informations suivantes :
     * - Identité de l'utilisateur (si disponible)
     * - Heure et IP de déconnexion
     * - Durée de la session (si calculable)
     * - Contexte de la requête (user agent, route, etc.)
     * - Type de déconnexion (volontaire, expiration, etc.)
     * 
     * Gère également les cas particuliers :
     * - Déconnexion après expiration de session (user = null)
     * - Sessions anormalement courtes (possibles problèmes)
     * - Déconnexions multiples simultanées
     *
     * @param LogoutEvent $event L'événement de déconnexion
     * 
     * @return void
     */
    public function onLogout(LogoutEvent $event): void
    {
        
    }

    /**
     * Listener déclenché lors d'une déconnexion.
     * Supprime la session de l'utilisateur de la base de données de manière asynchrone.
     *
     * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
     */
    public function onSession(LogoutEvent $event): void
    {
        
    }

    public function onRemoveUserCache(LogoutEvent $event): void
    {
        try {
            /** @var BaseUserInterface|null ; */
            $user = $event->getToken()?->getUser();
            if ($user instanceof BaseUserInterface) {
                $this->userProvider->invalidateUserCache($user->getId());

                // Log du succès (optionnel)
                $this->asyncDispatcher->dispatch(
                    LoggerInterface::class,
                    'info',
                    [
                        'User cache invalidated on logout',
                        [
                            'user_id' => $user->getId()
                        ]
                    ]
                );
            }
        } catch (\Throwable $th) {
            $this->asyncDispatcher->dispatch(
                LoggerInterface::class,
                'error',
                [
                    'Failed to invalidate user cache on logout',
                    [
                        'exception_class' => get_class($th),
                        'message' => $th->getMessage(),
                        'file' => $th->getFile(),
                        'line' => $th->getLine(),
                        'user_id' => $user?->getId() ?? 'unknown',
                        'trace' => $th->getTraceAsString()
                    ]
                ]
            );
        }
    }
    /**
     * Extrait les métadonnées de session (durée, ID, heure de début).
     *
     * @param Request $request La requête de déconnexion
     * 
     * @return array{session_id: string|null, duration: int|null, started_at: string|null}
     */
    private function extractSessionMetadata(Request $request): array
    {
        $session = $request->hasSession() ? $request->getSession() : null;

        if ($session === null) {
            return [
                'session_id' => null,
                'duration' => null,
                'started_at' => null,
            ];
        }

        $sessionId = $session->getId();
        $loginTime = $session->get('_security_login_time');
        $duration = null;
        $startedAt = null;

        if ($loginTime !== null) {
            $duration = time() - $loginTime;
            $startedAt = date('Y-m-d H:i:s', $loginTime);
        }

        return [
            'session_id' => $sessionId,
            'duration' => $duration,
            'started_at' => $startedAt,
        ];
    }

    /**
     * Dispatche une commande d'activité de manière asynchrone.
     *
     * //@param ActivityLogCommand $command La commande à dispatcher
     * 
     * @return void
     */
  /*  private function dispatchActivityLog(ActivityLogCommand $command): void
    {
        $this->asyncDispatcher->dispatch(
            ActivityLogCommandHandler::class,
            'handle',
            [$command]
        );
    }*/

    /**
     * Log une déconnexion réussie avec contexte utilisateur.
     *
     * @param BaseUserInterface $user L'utilisateur déconnecté
     * @param Request $request La requête de déconnexion
     * @param int|null $sessionDuration Durée de la session en secondes
     * 
     * @return void
     */
    private function logSuccessfulLogout(
        BaseUserInterface $user,
        Request $request,
        ?int $sessionDuration
    ): void {
        $this->logger?->info('Déconnexion utilisateur enregistrée', [
            'user_id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => method_exists($user, 'getEmail') ? $user->getEmail() : 'N/A',
            'ip' => $request->getClientIp(),
            'session_duration' => $sessionDuration !== null
                ? $this->formatDuration($sessionDuration)
                : 'N/A',
            'route' => $request->attributes->get('_route') ?? 'N/A',
        ]);
    }

    /**
     * Log une déconnexion anonyme (session expirée).
     *
     * @param Request $request La requête de déconnexion
     * 
     * @return void
     */
    private function logAnonymousLogout(Request $request): void
    {
        $this->logger?->debug('Déconnexion anonyme ou session expirée', [
            'ip' => $request->getClientIp(),
            'route' => $request->attributes->get('_route') ?? 'N/A',
        ]);
    }

    /**
     * Vérifie si le listener est activé via la configuration.
     *
     * @return bool True si activé, false sinon
     */
    private function isListenerEnabled(): bool
    {
        $enabled = $this->parameterBag->get('app.execute_listener');

        if (!$enabled) {
            $this->logger?->debug('LogoutSubscriber désactivé via configuration app.execute_listener');
        }

        return (bool) $enabled;
    }

    /**
     * Formate une durée en secondes en format lisible.
     *
     * @param int $seconds Durée en secondes
     * 
     * @return string Durée formatée (ex: "2h 15m 30s")
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return sprintf('%ds', $seconds);
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        $parts = [];

        if ($hours > 0) {
            $parts[] = sprintf('%dh', $hours);
        }

        if ($minutes > 0) {
            $parts[] = sprintf('%dm', $minutes);
        }

        if ($secs > 0 || empty($parts)) {
            $parts[] = sprintf('%ds', $secs);
        }

        return implode(' ', $parts);
    }
}
