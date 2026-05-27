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

use App\Entity\AbstractUser;
use App\Repository\SonataUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use libphonenumber\PhoneNumber;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
#[ORM\Entity(repositoryClass: SonataUserRepository::class)]
#[ORM\Table(name: "sonata_user")]
#[Vich\Uploadable]
#[Gedmo\SoftDeleteable]
class SonataUser extends AbstractUser implements
    UserInterface
{
    use SoftDeleteableEntity ;
    public const ROLE_DEFAULT = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue('IDENTITY')]
    #[ORM\Column(type: "integer")]
    #[Groups(['user:cache', 'user:security', 'user:read'])]
    protected int|string|null $id = null;

    #[Assert\NotBlank(message: 'Le nom complet est obligatoire.')]
    #[Assert\NotNull(message: 'Le nom complet ne peut pas être nul.')]
    #[Assert\Length(
        min: 6,
        max: 255,
        minMessage: 'Le nom complet doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom complet ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[Assert\Regex(
        pattern: '/^[\p{L}\p{N}\p{M}\s\.]{6,255}$/iu',
        message: 'Le nom ne peut contenir que des lettres (toutes langues), chiffres, espaces, et points.',
    )]
    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['user:cache', 'user:security', 'user:read'])]
    protected ?string $username = null;

    #[Assert\NotNull(message: 'L\'email ne peut pas être nul.')]
    #[Assert\Length(
        min: 6,
        max: 200,
        minMessage: 'L\'email doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[Assert\Email(
        message: '{{ value }} n\'est pas une adresse email valide.',
        mode: 'html5',
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/',
        message: 'L\'adresse email contient des caractères non autorisés.',
    )]
    #[ORM\Column(type: "string", length: 200, unique: true)]
    #[Groups(['user:cache', 'user:security', 'user:read'])]
    protected ?string $email = null;

    #[ORM\Column(type: "json", options: ['jsonb' => true])]
    #[Assert\NotBlank()]
    #[Assert\NotNull]
    #[Groups(['user:cache', 'user:security'])]
    protected array $roles = [];

    #[ORM\Column(type: 'string', length: 255, unique: true, nullable: true)]
    #[Groups(['user:cache', 'user:security'])]
    protected ?string $usernameCanonical = null;

    #[ORM\Column(type: 'string', length: 200, unique: true, nullable: true)]
    #[Groups(['user:cache', 'user:security'])]
    protected ?string $emailCanonical = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['user:cache', 'user:read'])]
    protected bool $enabled = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user:cache', 'user:security'])]
    protected ?string $salt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user:cache', 'user:security'])]
    protected ?string $password = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['user:cache', 'user:security'])]
    protected ?\DateTimeInterface $lastLogin = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user:cache', 'user:security'])]
    protected ?string $confirmationToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['user:cache', 'user:security'])]
    protected ?\DateTimeInterface $tokenRequestedAt = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    #[Groups(['user:cache', 'user:security'])]
    protected bool $emailVerified = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['user:cache', 'user:security'])]
    protected ?\DateTimeInterface $passwordRequestedAt = null;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    #[Groups(['user:cache', 'user:security'])]
    protected ?\DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(type: "datetime_immutable")]
    #[Groups(['user:cache', 'user:read'])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    #[Groups(['user:cache','user:read'])]
    protected ?\DateTimeInterface $updatedAt = null;

    #[Vich\UploadableField(mapping: 'avatars', fileNameProperty: 'avatarName')]
    #[Groups('user__')]
    protected ?File $avatarFile = null;

    /**
     * Timestamp pour Vich (obligatoire)
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['user:cache', 'user:read'])]
    protected ?\DateTimeInterface $avatarUpdatedAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user:cache', 'user:read'])]
    protected ?string $avatarName = null;

    #[Assert\Length(
        min: 6,
        max: 200,
        minMessage: 'Le profile complet doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le profile complet ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[ORM\Column(type: 'string',length: 200, nullable: true)]
    #[Assert\Regex(
        pattern: '/^[\p{L}\p{N}\p{M}\s\-\.&]{6,200}$/iu',
        message: 'Le profile ne peut contenir que des lettres (toutes langues), chiffres, espaces, tirets, et points.',
    )]
    #[Groups(['user:cache','user:read'])]
    protected ?string $profile = null;


    #[Assert\NotBlank()]
    #[Assert\Length(min: 8, max: 80)]
    #[Assert\NotNull]
    #[ORM\Column(type: 'phone_number',length:80)]
    #[Groups(['user:read'])]
    protected ?PhoneNumber $phone = null;

    #[Assert\Country(
        message: '{{ value }} n\'est pas un code pays valide (ISO 3166-1 alpha-3).',
        alpha3: true
    )]
    #[Assert\Length(min:3, max: 200)]
    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    #[Groups(['user:read'])]
    protected ?string $country;

    #[ORM\Column(type: "string", nullable: true)]
    #[Groups(['user:cache', 'user:read'])]
    protected ?string $slug = null;

    /**
     * Indique si cet utilisateur admin est aussi un membre visible
     * sur la page "À propos" du site.
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['user:cache', 'user:read'])]
    protected bool $isMember = false;

    /**
     * Position d'affichage dans la section équipe (null si pas membre).
     */
    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Groups(['user:cache', 'user:read'])]
    protected ?int $teamPosition = null;

    /**
     * Biographie publique affichée côté site (différente du champ "profile" admin).
     */
    #[Assert\Length(
        max: 1000,
        maxMessage: 'La biographie publique ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[Groups(['user:cache', 'user:read'])]
    #[Assert\Regex(
        pattern: '/<[^>]*>|<\/[^>]+>|&[#a-zA-Z0-9]+;|javascript\s*:|data\s*:|vbscript\s*:|on\w+\s*=|<\?(?:php)?|\?>|\{\{.*?\}\}|\$\{/ius',
        message: 'Le contenu ne peut pas contenir de balises HTML, PHP ou JavaScript.',
        match: false,
    )]
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $teamBio = null;

    /**
     * Initiale affichée si aucune photo n'est disponible (ex: "S", "É").
     */
    #[Assert\Length(
        max: 5,
        maxMessage: 'L\'initiale ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[ORM\Column(length: 5, nullable: true)]
    #[Groups(['user:cache', 'user:read'])]
    protected ?string $teamInitial = null;

    /**
     * Texte alternatif de la photo pour l'accessibilité / SEO.
     */
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le texte alternatif ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:cache', 'user:read'])]
    protected ?string $teamAltText = null;

    // Setter pour l'ID (nécessaire pour la désérialisation)
    public function setId(int|string $id):void
    {
        $this->id = $id;
    }

    /**
     * Get the value of profile
     */
    public function getProfile(): ?string
    {
        return $this->profile;
    }
 
    /**
     * Set the value of profile
     */
    public function setProfile(?string $profile): void 
    {
        $this->profile = $profile;
    }


    /**
     * Get the value of phone
     */
    public function getPhone(): ?PhoneNumber
    {
        return $this->phone;
    }

    /**
     * Set the value of phone
     */
    public function setPhone(?PhoneNumber $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * Get the value of country
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * Set the value of country
     */
    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }

    public function getAvatarFile(): ?File
    {
        return $this->avatarFile;
    }

    public function setAvatarFile(?File $avatarFile=null): void
    {
        $this->avatarFile = $avatarFile;

        if (null !== $avatarFile) {
            $this->avatarUpdatedAt = new \DateTime('now',new \DateTimeZone('UTC'));
        }
    }

    public function getAvatarName(): ?string
    {
        return $this->avatarName;
    }

    public function setAvatarName(?string $avatarName): void
    {
        $this->avatarName = $avatarName;
    }

    public function getAvatarUpdatedAt(): ?\DateTimeInterface
    {
        return $this->avatarUpdatedAt;
    }

    public function setAvatarUpdatedAt(?\DateTimeInterface $avatarUpdatedAt): void
    {
        $this->avatarUpdatedAt = $avatarUpdatedAt;
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->getUserIdentifier() !== $user->getUserIdentifier()) {
            return false;
        }

        return true;
    }

    public function getRolePrincipal(): string
    {
        if($this->isDirector()) return 'ROLE_DIRECTOR' ;

        if($this->isSuperAdmin()) return "ROLE_SUPER_ADMIN" ;

        if($this->isFounder()) return "ROLE_FOUNDER" ;
        
        return self::ROLE_DEFAULT;
    }

    public function isFounder(): bool
    {
        return $this->hasRole('ROLE_FOUNDER');
    }

    public function isDirector(): bool
    {
        return $this->hasRole('ROLE_DIRECTOR');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    public function isMember(): bool
    {
        return $this->isMember;
    }

    public function setIsMember(bool $isMember): static
    {
        $this->isMember = $isMember;
        return $this;
    }

    public function getTeamPosition(): ?int
    {
        return $this->teamPosition;
    }

    public function setTeamPosition(?int $teamPosition): static
    {
        $this->teamPosition = $teamPosition;
        return $this;
    }

    public function getTeamBio(): ?string
    {
        return $this->teamBio;
    }

    public function setTeamBio(?string $teamBio): static
    {
        $this->teamBio = $teamBio;
        return $this;
    }

    public function getTeamInitial(): ?string
    {
        return $this->teamInitial;
    }

    public function setTeamInitial(?string $teamInitial): static
    {
        $this->teamInitial = $teamInitial;
        return $this;
    }

    public function getTeamAltText(): ?string
    {
        return $this->teamAltText;
    }

    public function setTeamAltText(?string $teamAltText): static
    {
        $this->teamAltText = $teamAltText;
        return $this;
    }
}