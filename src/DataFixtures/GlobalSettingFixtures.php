<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\GlobalSetting;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\WhenNot;


#[WhenNot('prod')]
class GlobalSettingFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $setting = new GlobalSetting();

        $setting->setEmailContact([
            'proticeditions@gmail.com',
            'leseditionsprotic@gmail.com',
        ]);

        $setting->setPhonePrimary([
            '+22995869951',
        ]);

        $setting->setAddresses([
            "Campus d'Abomey-Calavi, K61-62 Rectorat annexe, Bénin",
        ]);

        $setting->setLegalInfos([
            'rccm' => 'RB/ABC/21 A 32987',
            'ifu'  => '0202112604781',
            'cnss' => '21312177',
        ]);

        $manager->persist($setting);
        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['settings', 'global_setting'];
    }
}
