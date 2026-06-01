<?php

declare(strict_types=1);
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ContactRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use libphonenumber\PhoneNumber;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/contact_requests',
            normalizationContext: ['groups' => ['contact_request:read']],
            paginationEnabled: false,
        ),
    ],
)]
#[Gedmo\SoftDeleteable]
#[ORM\Entity(repositoryClass: ContactRequestRepository::class)]
class ContactRequest
{
    use SoftDeleteableEntity;
    
    #[ORM\Id]
    #[ORM\GeneratedValue('IDENTITY')]
    #[ORM\Column(type: "integer")]
    #[Groups(['contact_request:read'])]
    private ?int $id = null;

    /**
     * Regex fullName :
     *  - \p{L}       → toutes lettres Unicode (accents, cyrillique, arabe…)
     *  - \p{N}       → chiffres Unicode
     *  - \s          → espaces, tabulations
     *  - \-          → tiret (noms composés : Marie-Claire)
     *  - \.          → point (abréviations : Dr., St.)
     *  - Interdit    → balises HTML, caractères dangereux < > & " { } ; = / \
     *
     * Exemples valides   : "Victor Hugo", "Ahmadou Kourouma",
     *                      "J.R.R. Tolkien", "N'Guessan Yao", "Marie-Claire Blais"
     * Exemples invalides : "<script>", "A" (trop court), "Hugo@web"
     */
    #[Assert\NotBlank(message: 'Le nom complet est obligatoire.')]
    #[Assert\NotNull(message: 'Le nom complet ne peut pas être nul.')]
    #[Assert\Length(
        min: 6,
        max: 255,
        minMessage: 'Le nom complet doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom complet ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[Assert\Regex(
        pattern: '/^[\p{L}\p{N}\p{M}\s\-\.]{6,255}$/iu',
        message: 'Le nom ne peut contenir que des lettres (toutes langues), chiffres, espaces, tirets, et points.',
    )]
    #[Groups(['contact_request:list', 'contact_request:write'])]
    #[ORM\Column(length: 255)]
    private ?string $fullName = null;

    /**
     * Regex email : standard RFC 5322 simplifié.
     * Exemples valides   : auteur@example.com, victor.hugo+livres@mail.fr
     * Exemples invalides : auteur@, @example.com, auteur @example.com
     */
    #[Assert\NotBlank(message: 'L\'adresse email est obligatoire.')]
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
    #[ORM\Column(type: "string", length: 200)]
    #[Groups(['contact_request:write'])]
    protected ?string $email = null;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 6, max: 255)]
    #[Assert\NotNull]
    #[Assert\Regex(
        pattern: '/^[\p{L}\p{N}\p{M}\s\-\.\p{P}\,\(\)]{6,255}$/iu',
        message: 'L\'objet du message contient des caractères non autorisés.',
    )]
    #[ORM\Column(length: 255)]
    #[Groups(['contact_request:write'])]
    private ?string $subject = null;

    /** 
     * Regex bio :
     *  - \p{L}\p{N}  → lettres et chiffres Unicode (toutes langues)
     *  - \s           → espaces et sauts de ligne (\n, \r)
     *  - \p{P}        → toute ponctuation Unicode (. , ; : ! ? ' " … « » — –)
     *  - \p{S}        → symboles (€ $ % @ & + = ~ ^ *)
     *  - Interdit     → balises HTML < > et séquences PHP <?
     *
     * Exemples valides   : texte libre avec accents, ponctuation, chiffres.
     * Exemples invalides : "<b>texte</b>", "<?php echo 1;", texte < 20 chars.
     */
    #[Assert\NotBlank(message: 'Le contenue est obligatoire.')]
    #[Assert\NotNull(message: 'Le contenue ne peut pas être nulle.')]
    #[Assert\Length(
        min: 20,
        max: 5000,
        minMessage: 'Le contenue doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le contenue ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[Assert\Regex(
        pattern: '/^[\p{L}\p{N}\p{M}\p{P}\s\-\.]$/iu',
        message: 'Le contenu ne peut pas contenir de balises HTML, PHP ou JavaScript.',
        match: true,
    )]
    #[ORM\Column(type: Types::TEXT, length: 5000)]
    #[Groups(['contact_request:write'])]
    private ?string $message = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[Assert\NotBlank(message: 'Le numéro de téléphone est obligatoire.')]
    #[Assert\NotNull(message: 'Le téléphone ne peut pas être nul.')]
    #[Assert\Length(min: 8, max: 80)]
    #[ORM\Column(type: 'phone_number', length: 80)]
    #[Groups(['contact_request:write'])]
    protected ?PhoneNumber $phone = null;

    #[Assert\Country(
        message: '{{ value }} n\'est pas un code pays valide (ISO 3166-1 alpha-3).',
        alpha3: true
    )]
    #[Assert\Length(min: 2, max: 200)]
    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    #[Groups(['contact_request:write'])]
    protected ?string $country;
    
    #[ORM\ManyToOne]
    private ?Book $book = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $sentAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): static
    {
        $this->book = $book;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
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
    public function setPhone(?PhoneNumber $phone): self
    {
        $this->phone = $phone;

        return $this;
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
    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function __toString():string { return $this->fullName ;}
}
