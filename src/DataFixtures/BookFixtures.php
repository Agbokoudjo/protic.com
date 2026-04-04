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

use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\WhenNot;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[WhenNot('prod')]
class BookFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const BOOK_REFERENCE_PREFIX = 'book_';

    public function __construct(
        private readonly string $projectDir,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $jsonPath = __DIR__ . '/data/books.json';

        if (!file_exists($jsonPath)) {
            throw new \RuntimeException(sprintf('Fichier introuvable : %s', $jsonPath));
        }

        $data = json_decode(file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);

        // Dossier temporaire pour stocker les images téléchargées
        $tmpDir = $this->projectDir . '/var/fixtures_tmp';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        foreach ($data as $index => $item) {
            $book = new Book();

            $book->setTitle($item['title']);
            $book->setSubtitle($item['subtitle'] ?? null);
            $book->setSummary($item['summary']);
            $book->setIsbn($item['isbn'] ?? null);

            if (!empty($item['publishedAt'])) {
                $book->setPublishedAt(new \DateTime($item['publishedAt']));
            }

            // ── Relation Author ───────────────────────────────
            /** @var \App\Entity\Author $author */
            $author = $this->getReference(
                AuthorFixtures::AUTHOR_REFERENCE_PREFIX . $item['author_index'],
                \App\Entity\Author::class
            );
            $book->setAuthor($author);

            // ── Relation Category ─────────────────────────────
            /** @var \App\Entity\Category $category */
            $category = $this->getReference(
                CategoryFixtures::CATEGORY_REFERENCE_PREFIX . $item['category_index'],
                \App\Entity\Category::class
            );
            $book->setCategory($category);

            // ── Image de couverture depuis picsum.photos ───────
            // On télécharge l'image et on crée un UploadedFile
            // pour que VichUploader la traite normalement
            if (!empty($item['cover_image_url'])) {
                $imageContent = @file_get_contents($item['cover_image_url']);

                if ($imageContent !== false) {
                    $tmpFile = $tmpDir . '/cover_' . $index . '.jpg';
                    file_put_contents($tmpFile, $imageContent);

                    $uploadedFile = new UploadedFile(
                        $tmpFile,
                        'cover_' . $index . '.jpg',
                        'image/jpeg',
                        null,
                        true // test mode = pas de validation strict
                    );

                    $book->setCoverFile($uploadedFile);
                } else {
                    // Si le téléchargement échoue, on met null
                    echo sprintf("⚠️  Image non téléchargée pour : %s\n", $item['title']);
                    $book->setCoverImage(null);
                }
            }

            $book->prePersist();
            $manager->persist($book);

            $this->addReference(self::BOOK_REFERENCE_PREFIX . $index, $book);
        }

        $manager->flush();

        // Nettoyage des fichiers temporaires
        array_map('unlink', glob($tmpDir . '/cover_*.jpg'));

        echo sprintf("✅ %d livres chargés.\n", count($data));
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            AuthorFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['catalogue', 'book'];
    }
}
