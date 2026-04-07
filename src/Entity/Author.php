<?php

declare(strict_types=1);
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\AuthorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use libphonenumber\PhoneNumber;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ApiResource(
    operations: [
        new GetCollection(
             uriTemplate: 'authors',
            normalizationContext: ['groups' => ['author:list']
            ]),
        new Get(normalizationContext: ['groups' => ['author:read']]),
    ]
)]
#[Vich\Uploadable]
#[Gedmo\SoftDeleteable]
#[ORM\Entity(repositoryClass: AuthorRepository::class)]
class Author
{
    use SoftDeleteableEntity;
    
    #[ORM\Id]
    #[ORM\GeneratedValue('IDENTITY')]
    #[ORM\Column(type: "integer")]
    #[Groups(['author:list', 'author:read', 'book:list', 'book:read'])]
    
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
    #[Groups(['author:list', 'author:read', 'book:list', 'book:read'])]
    #[Assert\Regex(
        pattern: '/^[\p{L}\p{N}\p{M}\s\-\.]{6,255}$/iu',
        message: 'Le nom ne peut contenir que des lettres (toutes langues), chiffres, espaces, tirets, et points.',
    )]
    #[ORM\Column(length: 255,unique: true)]
    private ?string $fullName = null;

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
    #[Assert\NotBlank(message: 'La biographie est obligatoire.')]
    #[Assert\NotNull(message: 'La biographie ne peut pas être nulle.')]
    #[Assert\Length(
        min: 20,
        max: 4000,
        minMessage: 'La biographie doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'La biographie ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[Assert\Regex(
        pattern: '/<|>|<\?/',
        message: 'La biographie ne peut pas contenir de balises HTML ou de code.',
        match: false,
    )]
    #[ORM\Column(type: Types::TEXT,length: 4000)]
    #[Groups(['author:read', 'book:read'])]
    private ?string $bio = null;

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
    #[Groups(['author:read', 'book:read', 'book:list'])]
    #[ORM\Column(type: "string", length: 200, unique: true)]
    protected ?string $email = null;

    #[ORM\Column(type: "datetime_immutable")]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[Assert\Image(
        minWidth: 50,
        maxWidth: 800,
        minHeight: 50,
        maxHeight: 800,
        allowSquare:false,
        extensions: ['jpg','png','jpeg','webp'],
        maxSize: '2M', // '2M' pour 2 Mégaoctets (SI) ou '2Mi' pour 2 Mébioctets (Binaire)
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
        maxSizeMessage: 'Le fichier est trop volumineux ({{ size }} {{ suffix }}). La taille maximale autorisée est {{ limit }} {{ suffix }}.',
        extensionsMessage: 'Veuillez télécharger une image valide (JPG, PNG, WEBP).'
    )]
    #[Vich\UploadableField(mapping: 'avatars', fileNameProperty: 'avatarName')]
    protected ?File $avatarFile = null;

    /**
     * Timestamp pour Vich (obligatoire)
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTimeInterface $avatarUpdatedAt = null;

    #[Groups(['author:read', 'book:read', 'book:list'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $avatarName = null;

    #[Assert\NotBlank(message: 'Le numéro de téléphone est obligatoire.')]
    #[Assert\NotNull(message: 'Le téléphone ne peut pas être nul.')]
    #[Assert\Length(min: 8, max: 80)]
    #[ORM\Column(type: 'phone_number', length: 80)]
    #[Groups(['author:read', 'book:read', 'book:list'])]
    protected ?PhoneNumber $phone = null;

    /**
     * Pays : code ISO 3166-1 alpha-2 (ex: BJ, FR, SN).
     * Validé par Assert\Country de Symfony.
     */
    #[Assert\NotBlank(message: 'Le pays est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 200,
        minMessage: 'Le pays doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le pays ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[Assert\Country(
        message: '{{ value }} n\'est pas un code pays valide (ISO 3166-1 alpha-3).',
        alpha3:true
    )]
    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    protected ?string $country;

    /**
     * @var Collection<int, Book>
     */
    #[ORM\OneToMany(targetEntity: Book::class, mappedBy: 'author', cascade: ['persist'],)]
    private Collection $books;

    public function __construct()
    {
        $this->books = new ArrayCollection();
    }

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
        $this->fullName = trim(preg_replace('/\s+/', ' ', $fullName));

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(string $bio): static
    {
        $this->bio = trim(strip_tags($bio));

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
        $this->country = $country !== null ? strtoupper(trim($country)) : null;
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

    public function prePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Get the value of email
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Set the value of email
     */
    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection<int, Book>
     */
    public function getBooks(): Collection
    {
        return $this->books;
    }

    public function addBook(Book $book): static
    {
        if (!$this->books->contains($book)) {
            $this->books->add($book);
            $book->setAuthor($this);
        }

        return $this;
    }

    public function removeBook(Book $book): static
    {
        if ($this->books->removeElement($book)) {
            // set the owning side to null (unless already changed)
            if ($book->getAuthor() === $this) {
                $book->setAuthor(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->fullName ?? '';
    }
}
