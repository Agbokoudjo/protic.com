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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 *
 */
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['category:list']]),
        new Get(normalizationContext: ['groups' => ['category:read']]),
    ]
)]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[Gedmo\SoftDeleteable]
class Category
{
    use SoftDeleteableEntity;

    #[Groups(['category:list', 'category:read', 'book:list', 'book:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue('IDENTITY')]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    /**
     * Regex : lettres (toutes langues Unicode), chiffres, espaces, tirets.
     * Interdit : balises HTML, caractères spéciaux dangereux (< > & " ' / \ ; = { }).
     * Exemples valides   : "Roman", "Bande dessinée", "Sci-Fi", "Développement personnel"
     * Exemples invalides : "<script>", "A", "AB" (trop court), chaîne > 100 chars
     */
    #[Groups(['category:list', 'category:read', 'book:list', 'book:read'])]
    #[Assert\NotBlank(message: 'Le nom de la catégorie est obligatoire.')]
    #[Assert\NotNull(message: 'Le nom ne peut pas être nul.')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[Assert\Regex(
        pattern: '/^[\p{L}\p{N}\s\-\p{P}]{3,100}$/u',
        message: 'Le nom ne peut contenir que des lettres, chiffres, espaces, tirets',
        match: true
    )]
    #[ORM\Column(length: 100, unique: true)]
    private ?string $name = null;

    /**
     * Regex : slug URL — uniquement lettres minuscules non accentuées, chiffres et tirets.
     * Pas de tiret en début ou fin. Pas de double tiret consécutif.
     * Exemples valides   : "roman", "bande-dessinee", "sci-fi-2024"
     * Exemples invalides : "-roman", "roman-", "roman--policier", "Roman", "roman slug"
     */
    #[Assert\Regex(
        pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        match: true,
        message: 'Le slug ne peut contenir que des lettres minuscules, des chiffres et des tirets (sans tiret en début, fin ou consécutifs).'
    )]
    #[Assert\Length(
        max: 120,
        maxMessage: 'Le slug ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[Groups(['category:list', 'category:read'])]
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $slug = null;

    /**
     * Regex : emoji Unicode uniquement.
     * Accepte 1 à 3 émojis (y compris les émojis composés avec ZWJ, variantes et modificateurs).
     * Bloque tout texte brut, balise HTML ou caractère ordinaire.
     *
     * Exemples valides   : "📖", "✍️", "🎭", "📚✍️"
     * Exemples invalides : "livre", "<i>", "abc", "📖abc"
     *
     * Note : la regex couvre les blocs Unicode emoji principaux :
     *   \x{1F300}-\x{1FAFF}  → émojis Misc Symbols, pictographes, supplémentaires
     *   \x{2600}-\x{27BF}    → symboles divers, dingbats
     *   \x{FE00}-\x{FEFF}    → variation selectors (️)
     *   \x{1F1E0}-\x{1F1FF}  → drapeaux (lettres régionales)
     *   \x{200D}             → ZWJ (zero-width joiner) pour les émojis composés
     */
    #[Assert\Length(
        max: 100,
        maxMessage: 'L\'icône ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[Assert\Regex(
        pattern: '/^[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE00}-\x{FEFF}\x{1F1E0}-\x{1F1FF}\x{200D}\x{20E3}]{1,10}$/u',
        message: 'L\'icône doit être un ou plusieurs emojis valides (max 3).',
    )]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: "datetime_immutable")]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, Book>
     */
    #[ORM\OneToMany(targetEntity: Book::class, mappedBy: 'category', cascade: ['persist'])]
    private Collection $books;

    public function __construct()
    {
        $this->books = new ArrayCollection();
    }

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
        $this->name = trim(preg_replace('/\s+/', ' ', $name));

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = strtolower(trim($slug));

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

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

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

    /**
     * Get the value of icon
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * Set the value of icon
     */
    public function setIcon(?string $icon): self
    {
        $this->icon = $icon !== null ? trim($icon) : null;

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
            $book->setCategory($this);
        }

        return $this;
    }

    public function removeBook(Book $book): static
    {
        if ($this->books->removeElement($book)) {
            // set the owning side to null (unless already changed)
            if ($book->getCategory() === $this) {
                $book->setCategory(null);
            }
        }

        return $this;
    }

    public function __toString():string{
        return $this->getName() ;
    }
}
