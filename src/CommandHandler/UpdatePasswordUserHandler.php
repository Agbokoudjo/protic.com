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

use App\Persistance\UserManagerInterface;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
final readonly class UpdatePasswordUserHandler
{
    public function __construct(
        private UserManagerInterface $userManager,
    ) {}

    public function handle(
       string|int $userId,
       string $plainPassword
    ): void {
        try {
            $user = $this->userManager->find($userId);

            if (null === $user) {
                return;
            }

            $user->setPlainPassword($plainPassword);
            $this->userManager->updatePassword($user);
            $user->preUpdate();
            $this->userManager->save($user);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
