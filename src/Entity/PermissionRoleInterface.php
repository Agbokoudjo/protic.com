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

namespace App\Entity;
use App\Entity\BaseUserInterface;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 * @example | id | name                | description             |
 *          | -- | ------------------- | ----------------------- |
 *          |  1 | PROJECT_MANAGER     | Gérer les projets       |
 *          | 2  | DOMAIN_EDITOR       | Éditer un domaine       |
 *          | 3  | NOTIFICATION\_ADMIN | Gérer les notifications |
 */
interface PermissionRoleInterface{

    public function getId():int|string|null ;

    public function getName():?string ;

    public function getDescription():?string;

    public function getContext():?string ;

    public function getCreatedBy():?BaseUserInterface ;

    public function setCreatedBy(?BaseUserInterface $user): void;
}