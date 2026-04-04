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

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\WhenNot;
use Symfony\Component\String\Slugger\SluggerInterface;

#[WhenNot('prod')]
class CategoryFixtures extends Fixture implements FixtureGroupInterface
{
    // Préfixe utilisé pour les références partagées
    // → AuthorFixtures (ou BookFixtures) pourra faire getReference()
    public const CATEGORY_REFERENCE_PREFIX = 'category_';

    public function __construct(
        private readonly SluggerInterface $slugger,
    ) {}

    public function load(ObjectManager $manager): void
    {
        // ── Charger le fichier JSON ───────────────────────────
        $jsonPath = __DIR__ . '/data/categories.json';

        if (!file_exists($jsonPath)) {
            throw new \RuntimeException(
                sprintf('Fichier de fixtures introuvable : %s', $jsonPath)
            );
        }

        $data = json_decode(file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);

        // ── Créer les entités ─────────────────────────────────
        foreach ($data as $index => $item) {
            $existingCategory = $manager->getRepository(Category::class)->findOneBy(['name' => $item['name']]);
            if ($existingCategory) {
                $category = $existingCategory;
            } else {
            $category = new Category();

            $category->setName($item['name']);

            // Slug : utilise la valeur JSON si présente, sinon génère depuis le nom
            $slug = !empty($item['slug'])
                ? $item['slug']
                : $this->slugger->slug($item['name'])->lower()->toString();
            $category->setSlug($slug);

            $category->setIcon($item['icon'] ?? null);
            $category->prePersist();

            $manager->persist($category);
            }
            // Référence partagée pour les autres fixtures
            // Ex: 'category_0', 'category_1', …
            $this->addReference(self::CATEGORY_REFERENCE_PREFIX . $index, $category);
        }

        $manager->flush();

        echo sprintf("✅ %d catégories chargées.\n", count($data));
    }

    // ── Groupe de fixtures ────────────────────────────────────
    public static function getGroups(): array
    {
        return ['catalogue', 'category'];
    }
}
