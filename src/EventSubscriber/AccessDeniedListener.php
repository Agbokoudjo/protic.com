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

use Twig\Environment;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
class AccessDeniedListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        private Environment $twigRender,
        #[Autowire(service: "sonata.admin.global_template_registry")]
        private TemplateRegistryInterface $templateRegistry,
        )
    {
        
    }
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onAccessDenied', 2],
        ];
    }

    public function onAccessDenied(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!($exception instanceof AccessDeniedException)) {
            return;
        }

        $user=$this->security->getUser();
        //lorsque l'utilisateur n'est pas connecter ou c'est un utilisateur quelconque qui cherche a tricher sur les url du back-office
        if(!$user){ 
            return ;
        }

       $event->setResponse(new Response(
        $this->twigRender->render(
                'bundles/TwigBundle/Exception/error403.html.twig',
                [
                    'layout'=> $this->templateRegistry->getTemplate('layout')
                ]
        )
        ,
            Response::HTTP_FORBIDDEN));
            
       return ;
    }
}