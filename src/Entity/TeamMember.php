<?php

declare(strict_types=1);

/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * Phone: +229 01 67 25 18 86
 * For more information, please feel free to contact the author.
 */

namespace App\Entity;

use App\Repository\TeamMemberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;

/**
 * Membre visible sur la page "À propos" de ProTIC Editions & Services.
 * Entité indépendante de SonataUser — un membre peut être lié à un compte admin
 * via la relation optionnelle $linkedUser, mais ce n'est pas obligatoire.
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
#[ORM\Entity(repositoryClass: TeamMemberRepository::class)]
#[ORM\Table(name: 'team_member')]
#[Vich\Uploadable]
#[Gedmo\SoftDeleteable]
class TeamMember
{
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue('IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * Nom complet ou intitulé du groupe (ex: "Équipe Éditoriale").
     */
    #[Assert\NotBlank(message: 'Le nom du membre est obligatoire.')]
    #[Assert\NotNull(message: 'Le nom ne peut pas être nul.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[Assert\Regex(
        pattern: '/^[\p{L}\p{N}\p{M}\s\.\-&\']{3,255}$/iu',
        message: 'Le nom ne peut contenir que des lettres, chiffres, espaces, tirets, points et apostrophes.',
    )]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * Fonction / poste (ex: "Directeur & Fondateur", "Logistique & Distribution").
     */
    #[Assert\NotBlank(message: 'Le rôle est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le rôle doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le rôle ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[Assert\Regex(
        pattern: '/^[\p{L}\p{N}\p{M}\s\.\-&\']{3,255}$/iu',
        message: 'Le rôle ne peut contenir que des lettres, chiffres, espaces, tirets, points et apostrophes.',
    )]
    #[ORM\Column(length: 255)]
    private ?string $role = null;

    /**
     * Biographie courte affichée sur la page About.
     */
    #[Assert\Length(
        max: 1000,
        maxMessage: 'La biographie ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[Assert\Regex(
        pattern: '/<|>|<\?/',
        message: 'La biographie ne peut pas contenir de balises HTML ou de code.',
        match: false,
    )]
    #[ORM\Column(type: 'text', nullable: true, length: 1000)]
    private ?string $bio = null;

    /**
     * Initiale affichée si aucune photo n'est disponible (ex: "S", "É", "D").
     */
    #[Assert\Length(
        min: 1,
        max: 5,
        minMessage: 'L\'initiale doit contenir au moins {{ limit }} caractère.',
        maxMessage: 'L\'initiale ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[ORM\Column(length: 5, nullable: true)]
    private ?string $initial = null;

    /**
     * Texte alternatif de l'image (accessibilité / SEO).
     */
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le texte alternatif ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $altText = null;

    /**
     * Fichier image (géré par VichUploader).
     */  
    #[Vich\UploadableField(mapping: 'team_photos', fileNameProperty: 'imageName')]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    /**
     * Timestamp Vich — obligatoire pour déclencher la mise à jour.
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $imageUpdatedAt = null;

    /**
     * Ordre d'affichage sur la page (du plus petit au plus grand).
     */
    #[Assert\PositiveOrZero(message: 'La position doit être un entier positif ou nul.')]
    #[ORM\Column(type: 'smallint', options: ['default' => 0])]
    private int $position = 0;

    /**
     * Permet de masquer un membre sans le supprimer.
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $visible = true;

    /**
     * Lien optionnel vers un compte SonataUser (ex : le Directeur).
     * Si le user admin est supprimé (soft-delete), la FK passe à NULL.
     */
    #[ORM\ManyToOne(targetEntity: SonataUser::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?SonataUser $linkedUser = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;
        return $this;
    }

    public function getInitial(): ?string
    {
        return $this->initial;
    }

    public function setInitial(?string $initial): static
    {
        $this->initial = $initial;
        return $this;
    }

    public function getAltText(): ?string
    {
        return $this->altText;
    }

    public function setAltText(?string $altText): static
    {
        $this->altText = $altText;
        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function getAvatarName():?string{ return $this->getImageName() ;  }

    public function setImageFile(?File $imageFile = null): static
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            $this->imageUpdatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        return $this;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): static
    {
        $this->imageName = $imageName;
        return $this;
    }

    public function getImageUpdatedAt(): ?\DateTimeInterface
    {
        return $this->imageUpdatedAt;
    }

    public function setImageUpdatedAt(?\DateTimeInterface $imageUpdatedAt): static
    {
        $this->imageUpdatedAt = $imageUpdatedAt;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;
        return $this;
    }

    public function getLinkedUser(): ?SonataUser
    {
        return $this->linkedUser;
    }

    public function setLinkedUser(?SonataUser $linkedUser): static
    {
        $this->linkedUser = $linkedUser;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? 'Nouveau membre';
    }

}
