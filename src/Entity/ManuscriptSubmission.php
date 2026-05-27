<?php

declare(strict_types=1);
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ManuscriptSubmissionRepository;
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
            uriTemplate: 'manuscript_submissions',
            normalizationContext: ['groups' => ['manuscript:read']],
            paginationEnabled: false,
        ),
    ],
)]
#[Vich\Uploadable]
#[Gedmo\SoftDeleteable]
#[ORM\Entity(repositoryClass: ManuscriptSubmissionRepository::class)]
class ManuscriptSubmission
{
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue('IDENTITY')]
    #[ORM\Column(type: "integer")]
    #[Groups(['manuscript:read'])]
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
    protected ?string $email = null;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 10, max: 255)]
    #[Assert\NotNull]
    #[Assert\Regex(
        pattern: '/^[\p{L}\p{N}\p{M}\s\-\.]{6,255}$/iu',
        message: 'L\'objet du message  ne peut contenir que des lettres (toutes langues), chiffres, espaces, tirets, et points.',
    )]
    #[ORM\Column(length: 255)]
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
    #[Assert\NotBlank(message: 'Votre message  est obligatoire.')]
    #[Assert\NotNull(message: 'Votre message ne peut pas être nulle.')]
    #[Assert\Length(
        min: 20,
        max: 5000,
        minMessage: 'Votre message doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Votre message ne peut pas dépasser {{ limit }} caractères.',
    )]
    #[Assert\Regex(
        pattern: '/<[^>]*>|<\/[^>]+>|&[#a-zA-Z0-9]+;|javascript\s*:|data\s*:|vbscript\s*:|on\w+\s*=|<\?(?:php)?|\?>|\{\{.*?\}\}|\$\{/ius',
        message: 'Le contenu ne peut pas contenir de balises HTML, PHP ou JavaScript.',
        match: false,
    )]
    #[ORM\Column(type: Types::TEXT, length: 5000)]
    private ?string $message = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[Assert\NotBlank(message: 'Le numéro de téléphone est obligatoire.')]
    #[Assert\NotNull(message: 'Le téléphone ne peut pas être nul.')]
    #[Assert\Length(min: 8, max: 80)]
    #[ORM\Column(type: 'phone_number', length: 80)]
    protected ?PhoneNumber $phone = null;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 3, max: 200)]
    #[Assert\Country(
        message: '{{ value }} n\'est pas un code pays valide (ISO 3166-1 alpha-3).',
        alpha3: true
    )]
    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    protected ?string $country;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $manuscriptFilename = null;

    #[Assert\File(
        maxSize: '10M',
        extensions: ['pdf', 'doc', 'docx', 'odt', 'txt'],
        extensionsMessage: 'Veuillez uploader un document valide (PDF, DOC, DOCX, ODT, TXT).',
        mimeTypes: [
            'application/pdf', // Pour .pdf
            'application/msword', // Pour .doc
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // Pour .docx
            'application/vnd.oasis.opendocument.text', // Pour .odt
            'text/plain' // Pour .txt
        ],
        mimeTypesMessage: 'Le type de fichier n\'est pas valide. Les formats de manuscrits acceptés sont PDF, Word, ODT et TXT.',
        maxSizeMessage: 'Le fichier est trop volumineux ({{ size }} {{ suffix }}). La taille maximale autorisée est {{ limit }} {{ suffix }}.'
    )]
    #[Vich\UploadableField(mapping: 'manuscript_file', fileNameProperty: 'manuscriptFilename')]
    private ?File $manuscriptFile = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(\DateTimeImmutable $submittedAt): static
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    /**
     * Get the value of manuscriptfilename
     */
    public function getManuscriptfilename(): ?string
    {
        return $this->manuscriptFilename;
    }

    /**
     * Set the value of manuscriptfilename
     */
    public function setManuscriptfilename(?string $manuscriptfilename): self
    {
        $this->manuscriptFilename = $manuscriptfilename;

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

    /**
     * Get the value of manuscriptfile
     */
    public function getManuscriptfile(): ?File
    {
        return $this->manuscriptFile;
    }

    /**
     * Set the value of manuscriptfile
     */
    public function setManuscriptfile(?File $manuscriptfile): self
    {
        $this->manuscriptFile = $manuscriptfile;

        if (null !== $manuscriptfile) {
            $this->updatedAt =  new \DateTime('now', new \DateTimeZone('UTC'));
        }

        return $this;
    }

    /**
     * Get the value of fullName
     */
    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    /**
     * Set the value of fullName
     */
    public function setFullName(?string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function __toString():string{ return $this->fullName ;}
}
