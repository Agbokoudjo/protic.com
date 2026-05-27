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
use App\Persistance\UserSessionManagerInterface;
use App\Queue\AsyncMethodDispatcherInterface;
use App\Service\DeviceFingerprintUserAgent ;
use App\Service\UserContextTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * Subscriber pour l'enregistrement des tentatives de connexion (succès et échecs).
 * 
 * Responsabilités :
 * - Enregistrer la date/IP de dernière connexion réussie
 * - Logger les connexions réussies dans le système d'activité
 * - Logger les tentatives de connexion échouées pour analyse de sécurité
 * -Enregistre le timestamp de connexion dans la session.
 * 
 * Tous les traitements sont effectués de manière asynchrone pour ne pas
 * impacter les performances de l'authentification.
 *
 * @internal Ce subscriber est appelé automatiquement par Symfony Security
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package App\UI\Http\EventSubscriber
 */
final class LoginSubscriber implements EventSubscriberInterface
{
    use UserContextTrait;
    use DeviceFingerprintUserAgent ;
    
    /**
     * Flag posé dans la session pour indiquer à SessionControlListener
     * qu'il doit enregistrer la session en BDD sur la prochaine requête.
     * À ce moment-là, le sessionId final (post-migration) est connu.
     */
    public const PENDING_SESSION_REGISTRATION = '_pending_session_registration';
    /** Clé de session stockant le timestamp de connexion (utilisé par LogoutSubscriber). */
    private const SESSION_LOGIN_TIME_KEY = '_security_login_time';

    public function __construct(
        private readonly AsyncMethodDispatcherInterface $asyncMethodDispatcher,
        private  readonly UserSessionManagerInterface $sessionManager,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // Connexion interactive (formulaire classique, HTTP Basic…)
            SecurityEvents::INTERACTIVE_LOGIN => [
                ['onLastLoginUpdate',    0],   // Mise à jour lastLogin
                ['onLoginSuccessActivity', -1], // Logging activité,
            ],
             LoginSuccessEvent::class => [
                ['onSessionLoginTime', 100],
                ['onMarkPendingSession',  0],
            ],
            LoginFailureEvent::class => ['onLoginFailure', -100]
        ];
    }

    /**
     * Enregistre le timestamp de connexion dans la session.
     *
     * Cette valeur est lue par LogoutSubscriber pour calculer la durée de session.
     * Priorité 100 → s'exécute avant tous les autres listeners de cet événement.
     */
     public function onSessionLoginTime(LoginSuccessEvent $event): void
    {
        $session = $event->getRequest()->getSession();
        $session->set(
            self::SESSION_LOGIN_TIME_KEY,
            $session->getMetadataBag()->getCreated()
        );
    }

    /**
     * Met à jour la date et l'IP de dernière connexion.
     * (Désactivé temporairement — décommenter pour activer)
     */
    public function onLastLoginUpdate(InteractiveLoginEvent $event): void
    {
        // TODO : activer le dispatch asynchrone vers LoggerLoginUseCase
    }

    /**
     * Enregistre l'activité de connexion dans le système de logs.
     * (Désactivé temporairement — décommenter pour activer)
     */
    public function onLoginSuccessActivity(InteractiveLoginEvent $event): void
    {
        // TODO : activer le dispatch asynchrone vers ActivityLogCommandHandler
    }

    /**
     * Enregistre les tentatives de connexion échouées.
     * (Désactivé temporairement — décommenter pour activer)
     */
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        // TODO : activer le dispatch asynchrone vers ActivityLogCommandHandler
    }

    /**
     * Pose un flag dans la session.
     * Symfony va régénérer le sessionId (migrate) APRÈS cet événement.
     * Le flag sera copié dans la nouvelle session grâce à "migrate".
     * SessionControlListener lira ce flag sur la première vraie requête
     * et enregistrera le sessionId final en BDD.
     */
    public function onMarkPendingSession(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!($user instanceof BaseUserInterface)) {
            return;
        }

        $session = $event->getRequest()->getSession();
        // Ne pas reposer le flag si déjà présent
        // (évite le double appel si remember-me + formulaire)
        if ($session->has(self::PENDING_SESSION_REGISTRATION)) {
            return;
        }

        // Stocker l'identifiant utilisateur dans le flag
        // (le sessionId sera lu dans SessionControlListener
        //  où il sera déjà le sessionId final post-migration)
        $session->set(self::PENDING_SESSION_REGISTRATION, [
            'userIdentifier'    => $user->getUserIdentifier(),
            'ipAddress'         => $event->getRequest()->getClientIp(),
            'userAgent'         => $event->getRequest()->headers->get('User-Agent'),
            'deviceFingerprint' => $this->buildDeviceFingerprint($event->getRequest()),
        ]);

        $this->logger?->debug('Flag pending session posé', [
            'user' => $user->getUserIdentifier(),
        ]);
    }
}