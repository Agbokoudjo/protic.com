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

use App\Event\UpdatePasswordUserEvent;
use App\Security\Provider\UserProvider;
use App\Service\Mailing\PriorityInterface;
use App\Service\Mailing\SystemMailer;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Subscriber pour gérer les notifications changements de mots de passe de comptes utilisateurs.
 * 
 * Envoie automatiquement un email à l'utilisateur pour l'informer du changement
 * de mots de passe de son compte 
 *
 * @internal Ce subscriber est appelé automatiquement par l'EventDispatcher
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package App\Infrastructure\Listener\User
 */
final class UpdatePasswordUserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SystemMailer $systemMailer,
        private readonly ParameterBagInterface $parameterBag,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
        private UserProvider $userProvider
    ) {}

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            UpdatePasswordUserEvent::class => 'onUpdatePasswordUserEvent'
        ];
    }

    /**
     * Gère l'événement de changement de mots de passe du compte utilisateur.
     *
     * Envoie un email de notification à l'utilisateur concerné.
     *
     * @param UpdatePasswordUserEvent $event L'événement contenant les informations du compte
     * 
     * @return void
     */
    public function onUpdatePasswordUserEvent(UpdatePasswordUserEvent $event): void
    {
        $user = $event->getUser();

        try {
            $siteName = $this->parameterBag->get('app.site_name');
            $loginUrl = $this->urlGenerator->generate(
                'app_admin_user_login',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $this->systemMailer->send(
                $user->getEmail(),
                \sprintf('Nouveau Mot de Passe Temporaire - %s',$siteName),
                'email/profile/regenerate_tempory_password_user.html.twig',
                [
                'username' => $user->getUsername(),
                'url_login' => $loginUrl,
                'NAME_SITE' => $siteName,
                'password_user'=>$event->getGeneratePassword()
            ],
                PriorityInterface::PRIORITY_HIGH
            );

            $this->logger?->info('Email de changement de mots de passe envoyer', [
                'user_email' => $user->getEmail()
            ]);
            $this->userProvider->invalidateUserCache($user->getId()) ;
        } catch (\Exception $e) {
            $this->logger?->error('Échec de l\'envoi de l\'email de changement de mots de passe envoyer', [
                'user_email' => $user->getEmail(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
