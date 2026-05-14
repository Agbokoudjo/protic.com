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

namespace App\CommandHandler;

use App\CommandHandler\UpdatePasswordUserHandler;
use App\Event\UpdatePasswordUserEvent;
use App\Persistance\UserManagerInterface;
use App\Service\GenerateTemporaryPasswordService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class GenerateTemporaryPasswordHandler
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private UserManagerInterface $userManager,
        private UpdatePasswordUserHandler $updatePasswordService,
        private GenerateTemporaryPasswordService $generateTemporatyPasswordService){}

    public function process(int|string $userId):void{
        try {
            $plainTemporyPasswordUser = $this->generateTemporatyPasswordService->generateTemporaryPassword();

            $this->updatePasswordService->handle($userId, $plainTemporyPasswordUser) ;
            
            $user = $this->userManager->find($userId);
            $this->eventDispatcher->dispatch(
                new UpdatePasswordUserEvent($user, $plainTemporyPasswordUser)
            );
        } catch (\Throwable $th) {
            throw $th;
        }
    }  
}
