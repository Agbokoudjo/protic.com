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

use App\Entity\ContactRequest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\DependencyInjection\Attribute\WhenNot;

#[WhenNot('prod')]
class ContactRequestFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function __construct(
        private readonly PhoneNumberUtil $phoneNumberUtil,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $jsonPath = __DIR__ . '/data/contact_requests.json';

        if (!file_exists($jsonPath)) {
            throw new \RuntimeException(sprintf('Fichier introuvable : %s', $jsonPath));
        }

        $data = json_decode(file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);

        foreach ($data as $item) {
            $contact = new ContactRequest();

            $contact->setFullName($item['fullName']);
            $contact->setEmail(strtolower(trim($item['email'])));
            $contact->setSubject($item['subject']);
            $contact->setMessage($item['message']);
            $contact->setStatus($item['status']);
            $contact->setCountry($item['country']);

            // ── Téléphone ─────────────────────────────────────
            try {
                $phone = $this->phoneNumberUtil->parse($item['phone'] ?? "+229 0167251886", null);
                $contact->setPhone($phone);
            } catch (\libphonenumber\NumberParseException $e) {
                echo sprintf("⚠️  Téléphone invalide pour %s : %s\n", $item['fullName'], $e->getMessage());
                continue;
            }

            // ── Relation Book ─────────────────────────────────
            /** @var \App\Entity\Book $book */
            $book = $this->getReference(
                BookFixtures::BOOK_REFERENCE_PREFIX . $item['book_index'],
                \App\Entity\Book::class
            );
            $contact->setBook($book);

            // ── Date d'envoi ──────────────────────────────────
            $contact->setSentAt(new \DateTimeImmutable($item['sentAt']));

            $manager->persist($contact);
        }

        $manager->flush();

        echo sprintf("✅ %d demandes de contact chargées.\n", count($data));
    }

    public function getDependencies(): array
    {
        return [
            BookFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['demande', 'contact'];
    }
}
