<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\FaqRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FaqRepository::class)]
#[Gedmo\SoftDeleteable]
#[ApiResource(
    shortName: 'FAQ',
    operations: [
        /* ── Liste publique paginée ── */
        new GetCollection(
            uriTemplate: '/faqs',
            normalizationContext: ['groups' => ['faq:read']],
            paginationEnabled: true,
            paginationItemsPerPage: 8,
            paginationMaximumItemsPerPage: 20,
        ),
        /* ── Détail public ── */
        new Get(
            uriTemplate: '/faqs/{id}',
            normalizationContext: ['groups' => ['faq:read']],
        ),
        /* ── Soumission d'une question par un visiteur ── */
        new Post(
            uriTemplate: '/faqs/ask',
            denormalizationContext: ['groups' => ['faq:ask']],
            normalizationContext: ['groups' => ['faq:read']],
            validationContext: ['groups' => ['Default', 'faq:ask']],
        ),
    ],
    order: ['position' => 'ASC', 'createdAt' => 'DESC'],
    /* Ne retourne que les FAQ publiées sur les endpoints publics */
    paginationClientItemsPerPage: true,
)]
class Faq
{
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue('IDENTITY')]
    #[ORM\Column(type: 'integer')]
    #[Groups(['faq:read'])]
    private ?int $id = null;

    /**
     * Question posée (par un visiteur ou saisie par l'admin)
     */
    #[Assert\NotBlank(message: 'La question ne peut pas être vide.', groups: ['Default', 'faq:ask'])]
    #[Assert\Length(
        min: 10,
        max: 500,
        minMessage: 'La question doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'La question ne peut pas dépasser {{ limit }} caractères.',
        groups: ['Default', 'faq:ask']
    )]
    #[Assert\Regex(
        pattern: '/<|>|<\?/',
        message: 'La question ne peut pas contenir de balises HTML ou de code.',
        match: false,
        groups: ['Default', 'faq:ask']
    )]
    #[ORM\Column(type: Types::TEXT,length:500)]
    #[Groups(['faq:read', 'faq:ask'])]
    private ?string $question = null;

    /**
     * Réponse rédigée par l'administrateur ProTIC
     * Null = question en attente de réponse (non publiée)
     */
    #[Assert\Length(max: 3000, maxMessage: 'La réponse ne peut pas dépasser {{ limit }} caractères.')]
    #[Assert\Regex(
        pattern: '/<\?/',
        message: 'La réponse ne peut pas contenir de code PHP.',
        match: false,
    )]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['faq:read'])]
    private ?string $answer = null;

    /**
     * Publiée = visible sur le site public
     * Une FAQ sans réponse ne doit jamais être publiée
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['faq:read'])]
    private bool $published = false;

    /**
     * Ordre d'affichage manuel (0 = premier)
     */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['faq:read'])]
    private int $position = 0;

    /**
     * Email du visiteur qui a posé la question (optionnel, non exposé en public)
     */
    #[Assert\Email(groups: ['faq:ask'])]
    #[Assert\Length(max: 200, groups: ['faq:ask'])]
    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $askerEmail = null;

    /**
     * Catégorie / thème de la FAQ (ex: "Publication", "Tarifs", "Distribution")
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['faq:read', 'faq:ask'])]
    private ?string $category = null;

    #[ORM\Column(type: "datetime_immutable")]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    /* ── Getters / Setters ── */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }
    public function setQuestion(string $question): static
    {
        $this->question = $question;
        return $this;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }
    public function setAnswer(?string $answer): static
    {
        $this->answer = $answer;
        return $this;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }
    public function setPublished(bool $published): static
    {
        $this->published = $published;
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

    public function getAskerEmail(): ?string
    {
        return $this->askerEmail;
    }
    public function setAskerEmail(?string $email): static
    {
        $this->askerEmail = $email;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }
    public function setCategory(?string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function __toString(): string
    {
        return mb_substr($this->question ?? '', 0, 80) . '…';
    }

    public function prePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
