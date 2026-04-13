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

use App\Event\UserAccountEmailVerificationEvent;
use App\Security\Encryption\IdEncryptionInterface;
use App\Service\Mailing\PriorityInterface;
use App\Service\Mailing\SystemMailer;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Subscriber pour gérer les notifications de verification de comptes utilisateurs.
 * 
 * @internal Ce subscriber est appelé automatiquement par l'EventDispatcher
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package App\Infrastructure\EventListener\User
 */
final class UserAccountEmailVerificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SystemMailer $systemMailer,
        private UrlGeneratorInterface $router,
        private ParameterBagInterface $params,
        private IdEncryptionInterface $idEncriptionService
    ) {}
    
    public static function getSubscribedEvents(): array
    {
        return [
            UserAccountEmailVerificationEvent::class => 'onUserAccountCreated',
        ];
    }

    public function onUserAccountCreated(UserAccountEmailVerificationEvent $event):void{

        $idEncription=$this->idEncriptionService->encryptId($event->getUserId());
        $confirmationUrl=$this->router->generate(
            'app.verify.email',[
                'token' => $event->getRawToken(),
                'id'=>$idEncription
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $siteName = $this->params->get('app.site_name');

        $subject = \sprintf('Votre compte %s a été créé', $siteName);

        $this->systemMailer->send(
            $event->getEmail(),
            $subject,
            'email/profile/admin_account_confirmation.html.twig',
            [
                'recipientEmail' => $event->getEmail(),
                'username'=> $event->getUsername(),
                'confirmationUrl' => $confirmationUrl,
                'siteName' => $siteName,
            ],
            PriorityInterface::PRIORITY_HIGH
        );
    }
}
