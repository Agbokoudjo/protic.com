<?php

namespace App\Repository;

use App\Entity\Author;
use App\Repository\ApiCacheInvalidatorForCatalogueInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @extends ServiceEntityRepository<Author>
 */
class AuthorRepository extends ServiceEntityRepository  implements ApiCacheInvalidatorForCatalogueInterface
{
    public function __construct(
        ManagerRegistry $registry,
        #[Target("cache.api_authors")]
        private readonly TagAwareCacheInterface $cacheApiAuthors,
        #[Target("cache.api_books")]
        private readonly TagAwareCacheInterface $cacheApiBooks
    )
    {
        parent::__construct($registry, Author::class);
    }

    public function findAllForSitemap(int $batchSize = 100): \Generator
    {
        $offset = 0;
        do {
            $results = $this->createQueryBuilder('a')
                ->select('partial a.{id, updatedAt}')
                ->orderBy('a.id', 'ASC')
                ->setMaxResults($batchSize)
                ->setFirstResult($offset)
                ->getQuery()
                ->toIterable();

            $count = 0;
            foreach ($results as $author) {
                yield $author;
                $count++;
            }

            $offset += $batchSize;
        } while ($count === $batchSize);
    }

    public function findAuthorsForApi(int $page, int $perPage): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.fullName', 'ASC')
            ->setMaxResults($perPage)
            ->setFirstResult(($page - 1) * $perPage)
            ->getQuery()
            ->enableResultCache(
                1209600,
                sprintf('authors_page_%d_per_%d', $page, $perPage)
            )
            ->getResult();
    }

    // Pour Get /api/authors/{id}
    public function findAuthorForApi(int $id): ?Author
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.books', 'b')->addSelect('b')
            ->where('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->enableResultCache(
                1209600,
                sprintf('author_item_%d', $id)
            )
            ->getOneOrNullResult();
    }
    
    public function invalidateForEntity(object $entity): void
    {
        if (!($entity instanceof Author)) return;

        try {
            $this->cacheApiAuthors->invalidateTags([
                self::CACHE_CATALOGUE_SHARED_TAG,
                self::CACHE_AUTHOR_TAG,
            ]);
            // invalide aussi books car Author est imbriqué dedans
            $this->cacheApiBooks->invalidateTags([
                self::CACHE_CATALOGUE_SHARED_TAG,
                self::CACHE_BOOKS_TAG,
            ]);
        } catch (\Throwable) {
        }
    }
}
