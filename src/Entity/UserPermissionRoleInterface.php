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

use App\Domain\BaseUserInterface;
use App\Entity\PermissionRoleInterface;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 * @example | user_id | permission_role_id   | scope (optionnel) |
 *          | --------| -------------------- | ----------------- |
 *          | 10      | 1                    | null              |
 *          | 10      | 2                    | domaine=Finance   |
 *          | 11      | 1                    | null              |
 */ 
interface UserPermissionRoleInterface
{
    public function getId(): int|string|null;

    public function getPermissionRole(): PermissionRoleInterface;

    public function  getAssignedAt(): \DateTimeInterface;

    public function getScope(): ?string ;

    public function setScope(?string $_scope): void;


    public function setAssignedAt(\DateTimeInterface $_assignedAt): void;

    public function SetPermissionRole(PermissionRoleInterface $_roles): void;

    public function getAssignedByUser(): BaseUserInterface ;

    public function getUserId(): int|string ;

    public function setUserId(string $userId): void ;
}
