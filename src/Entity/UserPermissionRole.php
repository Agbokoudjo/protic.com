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
use App\Entity\PermissionRole;
use App\Entity\PermissionRoleInterface;
use App\Entity\UserPermissionRoleInterface;
use App\Repository\UserPermissionRoleRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 * @example | user_id | permission_role_id   | scope (optionnel) |
 *          | --------| -------------------- | ----------------- |
 *          | 10      | 1                    | null              |
 *          | 10      | 2                    | domaine=Finance   |
 *          | 11      | 1                    | null              |
 */
#[ORM\Entity(repositoryClass: UserPermissionRoleRepository::class)]
#[ORM\Table(name: "user_permission_role")]
#[Gedmo\SoftDeleteable]
class UserPermissionRole implements UserPermissionRoleInterface
{
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected int|string|null $id = null;

    #[ORM\Column(type: 'integer')]
    protected int|string $userId;

    #[ORM\ManyToOne(targetEntity: PermissionRole::class, fetch: "EAGER")]
    #[ORM\JoinColumn(
        name: 'permission_role_id', 
        referencedColumnName: 'id', 
        nullable: false, 
        onDelete: 'CASCADE')]
    protected PermissionRole $roles;

    #[ORM\Column(type: 'datetime_immutable')]
    protected \DateTimeInterface $assignedAt;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    protected ?string $scope = null;

    final public function getId(): int|string|null{

        return $this->id ;
    }

    public function setRoles(PermissionRoleInterface $roles): void
    {
        $this->roles =$roles;
    }

    public function getRoles(): PermissionRoleInterface
    {
        return $this->roles;
    }

    public function getUserId():int|string{

        return $this->userId ;
    }

    public function setUserId(string $userId): void
    {
        $this->userId=$userId;
    }

    final public function getPermissionRole():PermissionRoleInterface{

        return $this->roles ;
    }

    final public function  getAssignedAt():\DateTimeInterface{

        return $this->assignedAt ;
    }

    final public function getAssignedByUser():BaseUserInterface{

        return $this->roles->getCreatedBy() ;
    }

    final public function getScope():?string{

        return $this->scope ;
    }

    final public function setScope(?string $_scope):void{

        $this->scope=$_scope ;
    }

    final public function setAssignedAt(\DateTimeInterface $_assignedAt):void{

        $this->assignedAt = $_assignedAt ;
    }

    final public function SetPermissionRole(PermissionRoleInterface $_roles):void{

        $this->roles =$_roles ;
    }

    public function __toString():string {

        return (string) $this->userId;
    }
}
