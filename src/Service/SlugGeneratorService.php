<?php
declare(strict_types=1);
/*
 * src/Service/SlugGeneratorService.php
 *
 * Génère un slug unique pour un Book depuis son titre.
 * Gère les collisions : si "mon-livre" existe déjà → "mon-livre-2", etc.
 *
 * Utilisé par :
 *   - BookAdmin (prePersist / preUpdate via SonataAdmin)
 *   - La commande console app:book:generate-slugs (livres existants)
 */

namespace App\Service;

use App\Entity\Book;
use App\Repository\BookRepository;
use Symfony\Component\String\Slugger\SluggerInterface;

final class SlugGeneratorService
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly BookRepository   $bookRepository,
    ) {}

    /**
     * Génère et affecte un slug unique sur le Book.
     * Ne fait rien si le livre a déjà un slug (sauf $force = true).
     */
    public function updateSlug(Book $book, bool $force = false): void
    {
        if ($book->getSlug() && !$force) {
            return;
        }

        $base = $this->slugger
            ->slug((string) $book->getTitle())
            ->lower()
            ->toString();

        $slug      = $base;
        $candidate = $base;
        $suffix    = 1;

        // Boucle de résolution des collisions
        while ($this->slugExists($candidate, $book->getId())) {
            $suffix++;
            $candidate = $base . '-' . $suffix;
        }

        $book->setSlug($candidate);
    }

    /**
     * Vérifie si ce slug est déjà pris par un AUTRE livre.
     * On exclut le livre courant pour ne pas bloquer sa propre mise à jour.
     */
    private function slugExists(string $slug, ?int $excludeId): bool
    {
        $qb = $this->bookRepository->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.slug = :slug')
            ->setParameter('slug', $slug);

        if ($excludeId !== null) {
            $qb->andWhere('b.id != :id')
               ->setParameter('id', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
