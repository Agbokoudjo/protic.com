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

use App\Entity\BaseUserInterface;
use App\Persistance\UserManagerInterface;
use App\QueueHandler\AsyncMethodDispatcher;
use Psr\Log\LoggerInterface;

/**
 * Applique les changements de vérification d'email à l'utilisateur.
 *
 */
final readonly class ApplyEmailVerificationService 
{
    public function __construct(
        private AsyncMethodDispatcher $asyncMethodDispatcher
    ) {}
    
    /**
     * @param  BaseUserInterface $user L'utilisateur dont l'email a été vérifié
     * @param  UserManagerInterface $userManager Le gestionnaire d'utilisateurs pour la persistance
     * 
     * @return void
     */
    public function handle(
        BaseUserInterface $user,
        UserManagerInterface $userManager
        ):void{
        
        // Marquer l'email comme vérifié
        $user->setIsEmailVerified(true);
        $user->setEmailVerifiedAt(new \DateTimeImmutable());

        // Nettoyer les données de token
        $user->setConfirmationToken(null);
        $user->setTokenRequestedAt(null);

        
        $this->asyncMethodDispatcher->dispatch(
            LoggerInterface::class,
            'debug',
            [
                'Compte non activé automatiquement (nécessite validation admin)',
                [
                    'user_id' => $user->getId(),
                ]
            ]
        );
        // Persistance des changements
        $userManager->save($user);
    }
}
