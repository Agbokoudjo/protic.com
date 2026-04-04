<?php

declare(strict_types=1);
namespace App\Entity;

use App\Repository\GlobalSettingRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as PhoneNumberConstraint ;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GlobalSettingRepository::class)]
#[Gedmo\SoftDeleteable]
class GlobalSetting
{
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue('IDENTITY')]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[Assert\All([
        new Assert\Email(message: "L'adresse email '{{ value }}' n'est pas valide."),
        new Assert\NotBlank(message: "L'email ne peut pas être vide.")
    ])]
    #[ORM\Column(type: "json", options: ['jsonb' => true])]
    private array $emailContact = [];

    #[Assert\All([
        new Assert\NotBlank(message: "Le numéro de téléphone ne peut pas être vide."),
        new Assert\Length(min: 8, max: 80),
        new PhoneNumberConstraint(
            format: PhoneNumberFormat::INTERNATIONAL,
            defaultRegion:"BJ",
            type:[PhoneNumberConstraint::PERSONAL_NUMBER, PhoneNumberConstraint::MOBILE],
            message: "Le numéro de téléphone '{{ value }}' n'est pas valide Ex:+229 01 XX XX XX XX"
        )
    ])]
    #[ORM\Column(type: "json", options: ['jsonb' => true])]
    private array $phonePrimary = [];

    #[Assert\All([
        new Assert\NotBlank(message: "L'adresse ne peut pas etre vide Ex:Campus d'Abomey-Calavi
                                        K61-62 Rectorat annexe, Bénin"),
        new Assert\Regex(
                pattern: '/^[\p{L}\p{N}\p{M}\s\-\.]{6,255}$/iu',
                message: 'L\'adresse ne peut contenir que des lettres (toutes langues), chiffres, espaces, tirets, et points.',
            )
    ])]
    #[ORM\Column(type: "json", options: ['jsonb' => true])]
    private array $addresses = [];

    #[Assert\Collection(
        fields: [
            // On utilise Optional pour que le champ ne plante pas si une clé manque
            'rccm' => new Assert\Optional([
                new Assert\NotBlank(),
                new Assert\Regex(
                    pattern: "/^[A-Z0-9\/ ]+$/i",
                    message: "Le format du RCCM est invalide."
                )
            ]),
            'ifu' => new Assert\Optional([
                new Assert\NotBlank(),
                new Assert\Regex(
                    pattern: "/^[0-9]+$/",
                    message: "L'IFU doit contenir uniquement des chiffres."
                )
            ]),
            'cnss' => new Assert\Optional([
                new Assert\NotBlank(),
                new Assert\Regex(
                    pattern: "/^[0-9]+$/",
                    message: "Le numéro CNSS est invalide."
                )
            ]),
        ],
        allowExtraFields: true, // Autorise le client à ajouter d'autres infos plus tard
        allowMissingFields: true
    )]
    /**
     * Stocke RCCM, IFU, CNSS, etc.
     */
    #[ORM\Column(type: "json", options: ['jsonb' => true])]
    private array $legalInfos = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmailContact(): array
    {
        return $this->emailContact;
    }

    public function setEmailContact(array $emailContact): static
    {
        $this->emailContact = $emailContact;

        return $this;
    }

    public function getPhonePrimary(): array
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        $phoneObjects = [];

        foreach ($this->phonePrimary as $phone) {
            if ($phone instanceof PhoneNumber) {
                $phoneObjects[] = $phone;
            } elseif (is_string($phone) && $phone !== '') {
                try {
                    $phoneObjects[] = $phoneUtil->parse($phone, 'BJ');
                } catch (\Exception $e) {
                    // Ignore les numéros mal formés pour éviter le crash
                }
            }
        }

        return $phoneObjects;
    }

    public function setPhonePrimary(array $phones): static
    {
        $util = PhoneNumberUtil::getInstance();
        $this->phonePrimary = array_map(function ($phone) use ($util) {
            return $phone instanceof PhoneNumber
                ? $util->format($phone, \libphonenumber\PhoneNumberFormat::E164)
                : $phone;
        }, $phones);

        return $this;
    }

    public function getAddresses(): array
    {
        return $this->addresses;
    }

    public function setAddresses(array $addresses): static
    {
        $this->addresses = $addresses;

        return $this;
    }

    public function getLegalInfos(): array
    {
        return $this->legalInfos;
    }

    public function setLegalInfos(array $legalInfos): static
    {
        $this->legalInfos = $legalInfos;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
