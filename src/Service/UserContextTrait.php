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

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
trait UserContextTrait{

    /**
     * Extrait le contexte utilisateur pour le logging.
     *
     * @param BaseUserInterface|null $user L'utilisateur (null si guest)
     * 
     * @return array<string, mixed> Le contexte utilisateur
     */
    protected function extractUserContext(?BaseUserInterface $user): array
    {
        if ($user === null) {
            return [
                'id' => null,
                'email' => 'anonymous',
                'username' => 'anonymous',
                'role' => 'ROLE_ANONYMOUS',
                'type' => 'guest',
            ];
        }

        try {
            return [
                'id' => $user->getId(),
                'email' => method_exists($user, 'getEmail') ? $user->getEmail() : 'N/A',
                'username' => $user->getUsername(),
                'role' => $user->getRolePrincipal()
            ];
        } catch (\Exception $e) {
            return [
                'id' => $user->getId(),
                'email' => method_exists($user, 'getEmail') ? $user->getEmail() : 'N/A',
                'username' => $user->getUsername(),
                'role' => $user->getRolePrincipal(),
                'type' => 'unknown',
            ];
        }
    }
}