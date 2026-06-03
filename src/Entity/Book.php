<?php
declare(strict_types=1);
namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\ApiResource\State\BookCachedProvider;
use App\Repository\BookRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ApiResource(
    operations: [
        // Liste paginée — utilisée par la page Catalogue ET la home
        new GetCollection(
            uriTemplate: '/books',
            normalizationContext: ['groups' => ['book:list', 'book:read']],
            paginationEnabled: true,
            paginationItemsPerPage: 12,      // défaut catalogue
            paginationClientItemsPerPage: true, // ?itemsPerPage=6 autorisé
            paginationMaximumItemsPerPage: 24,
            provider: BookCachedProvider::class,
        ),
        // Détail — utilisé si besoin d'une page livre seule
        new Get(
            normalizationContext: ['groups' => ['book:read']],
            provider: BookCachedProvider::class,
        ), 
    ]
)]
#[ApiFilter(OrderFilter::class, properties: ['publishedAt' => 'DESC', 'title' => 'ASC'])]
#[ApiFilter(SearchFilter::class, properties: ['category.slug' => 'partial', 'author.fullName' => 'partial'])]
#[Vich\Uploadable]
#[Gedmo\SoftDeleteable] 
#[ORM\Entity(repositoryClass: BookRepository::class)]
#[UniqueEntity(fields: ['isbn'], message: 'Ce numéro ISBN est déjà enregistré pour un autre ouvrage.')]
final class Book
{
    use SoftDeleteableEntity;
    #[Groups(['book:list', 'book:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue('IDENTITY')]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[Groups(['book:list', 'book:read'])]
    #[Assert\NotNull]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(
        min: 4,
        max: 255,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[Assert\Regex(
        pattern: '/[<>`\x00-\x1F\x7F\x{200B}-\x{200D}\x{FEFF}#$^{|}]/u',
        message: 'Le titre contient des caractères interdits.',
        match: false,
    )]
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[Groups(['book:list', 'book:read'])]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le sous-titre ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[Assert\Regex(
        pattern: '/[<>`\x00-\x1F\x7F\x{200B}-\x{200D}\x{FEFF}#$^{|}]/u',
        message: 'Le sous-titre contient des caractères interdits.',
        match: false,
    )]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subtitle = null;

    #[Groups(['book:read'])] // résumé complet seulement sur book:read (modale)
    #[Assert\NotNull]
    #[Assert\NotBlank(message: "Le résumé ne peut pas être vide.")]
    #[Assert\Length(
        min: 100,
        max: 10000,
        minMessage: 'Le résumé doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le résumé ne peut pas dépasser {{ limit }} caractères.',
    )]
    /*#[Assert\Regex(
        pattern: '/^[\p{L}\p{N}\p{M}\p{P}\s\-\.]$/iu',
        message: 'Le contenu ne peut pas contenir de balises HTML, PHP ou JavaScript.',
        match: true,
    )]*/
    #[ORM\Column(type: Types::TEXT,length:10000)]
    private ?string $summary = null;

    #[Groups(['book:list', 'book:read'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    #[Assert\Image(
        minWidth: 400,
        maxWidth: 1200,
        minHeight: 600,
        maxHeight: 1800,
        allowSquare: false,
        allowLandscape: false,
        allowPortrait: true,
        minRatio: 0.5,
        maxRatio: 0.8,
        corruptedMessage: "Le fichier image est corrompu.",
        extensions: ['jpg', 'png', 'jpeg', 'webp'],
        maxSize: '5M', 
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
        maxSizeMessage: 'Le fichier est trop volumineux ({{ size }} {{ suffix }}). La taille maximale autorisée est {{ limit }} {{ suffix }}.',
        extensionsMessage: 'Veuillez télécharger une image valide (JPG, PNG, WEBP).'
    )]
    #[Vich\UploadableField(mapping: 'cover_image_book', fileNameProperty: 'coverImage')]
    private ?File $coverFile = null;

    #[Groups(['book:read'])]
    #[Assert\Length(max: 17)]
    #[Assert\When(
        expression: 'this.getIsbn() !== null && this.getIsbn() !== ""',
        constraints: [
            new Assert\Length(
                max: 17,
                maxMessage: 'Le code ISBN ne peut pas dépasser {{ limit }} caractères.',
            ),
            new Assert\Isbn(
                type: null,         // ← détecte automatiquement ISBN-10 ou ISBN-13
                message: "Le numéro ISBN « {{ value }} » n'est pas valide.",
            ),
        ]
    )]
    #[ORM\Column(length: 17, nullable: true)]
    private ?string $isbn = null;

    #[Groups(['book:list', 'book:read'])]
    #[Assert\LessThanOrEqual(
        value: 'today',
        message: 'La date de parution ne peut pas être dans le futur.',
    )]
    #[Assert\GreaterThanOrEqual(
        value: '1450-01-01',                      // cohérent avec data-min-date
        message: 'La date de parution semble invalide.',
    )]
    #[Assert\Type(\DateTimeInterface::class)] 
    #[ORM\Column(nullable: true, type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $publishedAt = null;

    #[ORM\Column(type: "datetime_immutable")]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[Groups(['book:list', 'book:read'])] // ← expose l'auteur imbriqué
    #[Assert\NotNull(message: "Veuillez sélectionner un auteur.")]
    #[ORM\ManyToOne(
        targetEntity: Author::class,
        inversedBy: 'books'
    )]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Author $author = null;

    #[Groups(['book:list', 'book:read'])]   // ← expose la catégorie imbriquée
    #[Assert\NotNull(message: "La catégorie est obligatoire.")]
    #[ORM\ManyToOne(
        targetEntity: Category::class,
        inversedBy: 'books'
    )]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Category $category = null;

    #[Groups(['book:list', 'book:read'])]
    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $slug = null;

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle): static
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): static
    {
        $this->summary = $summary;

        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): static
    {
        $this->coverImage = $coverImage;

        return $this;
    }

    public function getCoverFile(): ?File
    {
        return $this->coverFile;
    }

    public function setCoverFile(?File $coverFile): static
    {
        $this->coverFile = $coverFile;

        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(?string $isbn): static
    {
        $this->isbn = $isbn;

        return $this;
    }

    public function getPublishedAt(): ?\DateTime
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTime $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
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

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(?Author $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function prePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function __toString():string{ return $this->title ; }
}
