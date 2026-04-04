<?php

declare(strict_types=1);
/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * Phone: +229 01 67 25 18 86
 * Company: INTERNATIONALES WEB APPS & SERVICES
 */

namespace App\DataFixtures;

use App\Entity\Author;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\DependencyInjection\Attribute\WhenNot;

#[WhenNot('prod')]
class AuthorFixtures extends Fixture implements FixtureGroupInterface
{
    // Préfixe pour les références partagées avec BookFixtures
    public const AUTHOR_REFERENCE_PREFIX = 'author_';

    public function __construct(
        private readonly PhoneNumberUtil $phoneNumberUtil,
    ) {}

    public function load(ObjectManager $manager): void
    {
        // ── Charger le fichier JSON ───────────────────────────
        $jsonPath = __DIR__ . '/data/authors.json';

        if (!file_exists($jsonPath)) {
            throw new \RuntimeException(
                sprintf('Fichier de fixtures introuvable : %s', $jsonPath)
            );
        }

        $data = json_decode(file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);

        // ── Créer les entités ─────────────────────────────────
        foreach ($data as $index => $item) {
            $existingAuthor = $manager->getRepository(Author::class)->findOneBy(['fullName' => $item['fullName']]);
            if ($existingAuthor) {
                $author = $existingAuthor;
            }else{
            $author = new Author();

            $author->setFullName($item['fullName']);
            $author->setBio($item['bio']);
            $author->setEmail(strtolower(trim($item['email'])));
            $author->setCountry($item['country']);

            // ── Conversion string → PhoneNumber object ────────
            // PhoneNumberUtil::parse() attend une string et une région par défaut
            // On passe null car les numéros JSON sont déjà en format international (+XXX)
            try {
                $phoneNumber = $this->phoneNumberUtil->parse($item['phone'], null);
                $author->setPhone($phoneNumber);
            } catch (\libphonenumber\NumberParseException $e) {
                // En fixtures on log et on continue plutôt que de crasher
                echo sprintf(
                    "⚠️  Téléphone invalide pour %s : %s\n",
                    $item['fullName'],
                    $e->getMessage()
                );
                continue;
            }

            // avatarFile est null — pas d'upload en fixtures
            $author->setAvatarName(null);
            $author->prePersist();

            $manager->persist($author);
            }
            // Référence partagée pour BookFixtures
            // Ex: 'author_0', 'author_1', …
            $this->addReference(self::AUTHOR_REFERENCE_PREFIX . $index, $author);
        }

        $manager->flush();

        echo sprintf("✅ %d auteurs chargés.\n", count($data));
    }

    // ── Groupe de fixtures ────────────────────────────────────
    public static function getGroups(): array
    {
        return ['catalogue', 'author'];
    }
}
