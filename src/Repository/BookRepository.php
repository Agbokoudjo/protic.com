<?php

namespace App\Repository;

use ApiPlatform\State\Pagination\TraversablePaginator;
use App\Entity\Book;
use App\Repository\ApiCacheInvalidatorForCatalogueInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository implements ApiCacheInvalidatorForCatalogueInterface{
    // Préfixe de cache pour les livres individuels (par slug)
    private const CACHE_BOOK_SLUG_PREFIX = 'book_slug_';

    // TTL pour le cache d'un livre individuel (1 heure)
    private const CACHE_BOOK_SLUG_TTL = 3600;

    public function __construct(
        ManagerRegistry $registry,
        #[Target("cache.api_books")]
        private readonly TagAwareCacheInterface $cacheApiBooks,
        #[Target('doctrine.result_cache.books')] 
        private readonly CacheItemPoolInterface $booksDoctrineCache,
        )
    {
        parent::__construct($registry, Book::class);
    }

    public function add(Book $entity, bool $flush = true): void
    {

        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findPublishedForSitemap(int $batchSize = 100): \Generator
    {
        $offset = 0;
        do {
            $results = $this->createQueryBuilder('b')
                ->select('partial b.{id, slug, updatedAt}') 
                ->orderBy('b.id', 'ASC')
                ->setMaxResults($batchSize)
                ->setFirstResult($offset)
                ->getQuery()
                ->toIterable();

            $count = 0;
            foreach ($results as $book) {
                yield $book;
                $count++;
            }

            $offset += $batchSize;
        } while ($count === $batchSize);
    }

    public function findBooksForApi(int $page, int $perPage): TraversablePaginator
    {
        $firstResult = ($page - 1) * $perPage;

        // COUNT — clé fixe, invalidée via clear() sur pool isolé
        $total = (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->getQuery()
            ->setResultCache($this->booksDoctrineCache)
            ->enableResultCache(604800, 'books_published_count')
            ->getSingleScalarResult();

        // Données — clé par page + perPage
        $items = $this->createQueryBuilder('b')
            ->leftJoin('b.author', 'a')->addSelect('a')
            ->leftJoin('b.category', 'c')->addSelect('c')
            ->orderBy('b.publishedAt', 'DESC')
            ->setMaxResults($perPage)
            ->setFirstResult($firstResult)
            ->getQuery()
            ->setResultCache($this->booksDoctrineCache)
            ->enableResultCache(
                604800,
                sprintf('books_page_%d_per_%d', $page, $perPage)
            )
            ->getResult();

        return new TraversablePaginator(
            new \ArrayIterator($items),
            $page,    // currentPage
            $perPage, // itemsPerPage
            $total    // totalItems ← COUNT caché 
        );
    }

    /**
     * Charge un livre par son slug avec mise en cache Redis.
     *
     * Le cache est taggué avec :
     *   - CACHE_CATALOGUE_SHARED_TAG (partagé avec la liste catalogue)
     *   - CACHE_BOOKS_TAG            (tag global livres)
     *   - un tag individuel "book_{id}" pour invalidation ciblée
     *
     * invalidateForEntity() est déjà appelé par SonataAdmin sur postUpdate /
     * postRemove — il vide ces tags, donc le cache par slug sera aussi purgé.
     */
    public function findOneBySlug(string $slug): ?Book
    {
        $cacheKey = self::CACHE_BOOK_SLUG_PREFIX . $slug;

        $bookId = $this->cacheApiBooks->get(
            $cacheKey,
            function (ItemInterface $item) use ($slug): int|false {
                $item->expiresAfter(self::CACHE_BOOK_SLUG_TTL);
                $item->tag([
                    self::CACHE_CATALOGUE_SHARED_TAG,
                    self::CACHE_BOOKS_TAG,
                ]);

                $book = $this->createQueryBuilder('b')
                    ->select('b', 'a', 'c')
                    ->leftJoin('b.author',   'a')
                    ->leftJoin('b.category', 'c')
                    ->where('b.slug = :slug')
                    ->setParameter('slug', $slug)
                    ->getQuery()
                    ->getOneOrNullResult();

                // On stocke l'ID (int) ou false si livre introuvable
                // false est stockable et évite une re-requête sur slug inconnu
                return $book?->getId() ?? false;
            }
        );

        if ($bookId === false || $bookId === null) {
            return null;
        }

        // On recharge par ID pour avoir une entité Doctrine managée
        // avec toutes ses relations déjà jointes ci-dessus au premier appel
        return $this->createQueryBuilder('b')
            ->select('b', 'a', 'c')
            ->leftJoin('b.author',   'a')
            ->leftJoin('b.category', 'c')
            ->where('b.id = :id')
            ->setParameter('id', $bookId)
            ->getQuery()
            ->setResultCache($this->booksDoctrineCache)
            ->enableResultCache(
                604800,
                sprintf('books_page_%s', $cacheKey)
            )
            ->getOneOrNullResult();
    }

    public function invalidateForEntity(object $entity): void
    {
        if (!($entity instanceof Book)) {
            return;
        }

        try {
            // Tags globaux (liste catalogue, toutes collections)
            $tagsToInvalidate = [
                self::CACHE_CATALOGUE_SHARED_TAG,
                self::CACHE_BOOKS_TAG,
            ];

            // Tag individuel du livre
            if ($entity->getId() !== null) {
                $tagsToInvalidate[] = 'book_' . $entity->getId();
            }

            $this->cacheApiBooks->invalidateTags($tagsToInvalidate);

            $this->cacheApiBooks->delete(self::CACHE_BOOK_SLUG_PREFIX . $entity->getSlug());

            $cacheKey= sprintf('books_page_%s',self::CACHE_BOOK_SLUG_PREFIX . $entity->getSlug()) ;
            $this->booksDoctrineCache->deleteItem($cacheKey);
            $this->booksDoctrineCache->clear();
        } catch (\Throwable) {
            // Silencieux si Redis/Cache ou Psr/CacheItemPoolInterface  indisponible
        }
    }
}
