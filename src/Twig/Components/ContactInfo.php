<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\GlobalSetting;
use App\Service\GlobalSettingProvider;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class ContactInfo
{
    /**
     * Prop optionnelle : permet de passer un GlobalSetting
     * directement depuis le template parent si déjà chargé.
     * Si null, le composant charge automatiquement depuis la BDD.
     */
    public ?GlobalSetting $setting = null;

    public function __construct(
        private readonly GlobalSettingProvider $globalSettingProvider
    ) {}

    private function getSetting(): ?GlobalSetting
    {
        if ($this->setting !== null) {
            return $this->setting;
        }

        return $this->globalSettingProvider->getSettings();
    }

    public function getAddresses(): array
    {
        return $this->getSetting()?->getAddresses() ?? [];
    }

    public function getPhones(): array
    {
        $phoneObjects = $this->getSetting()?->getPhonePrimary() ?? [];
        if (empty($phoneObjects)) {
            return [];
        }

        $util    = PhoneNumberUtil::getInstance();
        $phones  = [];

        foreach ($phoneObjects as $phone) {
            try {
                $phones[] = [
                    'display' => $util->format($phone, PhoneNumberFormat::INTERNATIONAL),
                    'href'    => $util->format($phone, PhoneNumberFormat::E164),
                ];
            } catch (\Throwable) {
                // Ignore les numéros mal formés
            }
        }

        return $phones;
    }

    public function getEmails(): array
    {
        return $this->getSetting()?->getEmailContact() ?? [];
    }

    public function getLegalInfos(): array
    {
        return $this->getSetting()?->getLegalInfos() ?? [];
    }

    public function hasData(): bool
    {
        return $this->getSetting() !== null;
    }
}
