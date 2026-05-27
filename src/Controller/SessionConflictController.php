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

namespace App\Controller;

use App\Persistance\UserSessionManagerInterface;
use App\QueueHandler\AsyncMethodDispatcher;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour la page de conflit de session.
 * Affiche un message lorsqu'un utilisateur tente de se connecter
 * alors qu'il est déjà connecté sur un autre appareil/navigateur.
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
#[Route(path: '/session-conflit/{id}', 
        name: 'app_session_conflict',
        methods: ['GET'])]
final class SessionConflictController extends AbstractController
{
    public function __construct(
        private readonly AsyncMethodDispatcher $asyncMethodDispatcher,
        private readonly  UserSessionManagerInterface $sessionManager
    ) {}

    public function __invoke(
          Request $request
    ): Response {
        try {
            $session = $request->getSession();
            $this->asyncMethodDispatcher->dispatch(
                LoggerInterface::class,
                'info',[
                    'Page de conflit de session affichée',
                    [
                        'userId' => $request->attributes->get('id') ?? 'anonymous',
                        'login_route' => "app_admin_user_login",
                        'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                    ]
                ]
            );

           $currentSessionId = $request->getSession()->getId();
            $this->sessionManager->removeSession($currentSessionId);
            
            return $this->render('session_conflict/index.html.twig');
        } catch (\Throwable $th) {
            // Log détaillé de l'erreur
            $this->asyncMethodDispatcher->dispatch(
                LoggerInterface::class,
                'error',
                [
                    'Erreur lors de l\'affichage de la page de conflit de session',
                    [
                        'exception_class' => get_class($th),
                        'exception_message' => $th->getMessage(),
                        'exception_code' => $th->getCode(),
                        'file' => $th->getFile(),
                        'line' => $th->getLine(),
                        'user' =>  $request->attributes->get('id'),
                        'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                        // Stack trace uniquement en dev
                        'trace' => ($_ENV['APP_ENV'] ?? 'prod') !== 'prod'
                            ? $th->getTraceAsString()
                            : null,
                    ]
                ]
            );

            $this->addFlash(
                'warning',
                'Une erreur est survenue. Veuillez vous reconnecter.'
            );
            return $this->redirectToRoute('app_home');
        }
    }
}
