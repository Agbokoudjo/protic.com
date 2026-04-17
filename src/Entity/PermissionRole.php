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

use App\Entity\PermissionRoleInterface;
use App\Entity\SonataUser;
use App\Repository\PermissionRoleRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 * @example | id | name                | description             |
 *          | -- | ------------------- | ----------------------- |
 *          |  1 | PROJECT_MANAGER     | Gérer les projets       |
 *          | 2  | DOMAIN_EDITOR       | Éditer un domaine       |
 *          | 3  | NOTIFICATION\_ADMIN | Gérer les notifications |
 */
#[ORM\Entity(repositoryClass: PermissionRoleRepository::class)]
#[ORM\Table(name: "permission_role")]
#[Gedmo\SoftDeleteable]
class PermissionRole implements PermissionRoleInterface
{
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue()]
    #[ORM\Column(type: 'integer')]
    protected int|string|null $id = null;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 5, max: 100)]
    #[Assert\NotNull]
    #[ORM\Column(type: 'string', length: 100, unique: true)]
    protected ?string $name = null;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 20, max: 10000)]
    #[Assert\NotNull]
    #[ORM\Column(type: 'text')]
    protected ?string $description = null;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 5, max: 200)]
    #[Assert\NotNull]
    #[ORM\Column(type: 'string', length: 200)]
    protected ?string $context= null;

    #[ORM\ManyToOne(targetEntity: SonataUser::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?SonataUser $createdBy = null;

    #[ORM\Column(type: "datetime_immutable")]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    protected ?\DateTimeInterface $updatedAt = null;

    /**
     * Get the value of id
     */
    public function getId(): int|string|null
    {
        return $this->id;
    }

    /**
     * Get the value of name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the value of name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the value of description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the value of description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;

    }

    /**
     * Get the value of context
     */
    public function getContext(): ?string
    {
        return $this->context;
    }

    /**
     * Set the value of context
     */
    public function setContext(?string $context): void
    {
        $this->context = $context;
    }

    public function getCreatedBy(): ?BaseUserInterface
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?BaseUserInterface $user): void
    {
        $this->createdBy = $user;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt = null): void
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt = null): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
    
    public function __toString():string
    {
        return $this->getName();
    }
}
