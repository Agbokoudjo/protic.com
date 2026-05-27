<?php

namespace App\Repository;

use App\Entity\Category;
use App\Repository\ApiCacheInvalidatorForCatalogueInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository implements ApiCacheInvalidatorForCatalogueInterface
{
    public function __construct(
        ManagerRegistry $registry,
        #[Target("cache.api_categories")]
        private readonly TagAwareCacheInterface $cacheApiCategories,
        #[Target("cache.api_books")]
        private readonly TagAwareCacheInterface $cacheApiBooks, // car Category imbriquée dans Book
    )
    {
        parent::__construct($registry, Category::class);
    }

    public function findAllForSitemap(int $batchSize = 50): \Generator
    {
        $offset = 0;
        do {
            $results = $this->createQueryBuilder('c')
                ->select('partial c.{id}')
                ->orderBy('c.id', 'ASC')
                ->setMaxResults($batchSize)
                ->setFirstResult($offset)
                ->getQuery()
                ->toIterable();

            $count = 0;
            foreach ($results as $category) {
                yield $category;
                $count++;
            }

            $offset += $batchSize;
        } while ($count === $batchSize);
    }

    // Toutes les catégories sans pagination (paginationEnabled: false)
    public function findCategoriesForApi(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->enableResultCache(
                2419200,
                'categories_all'   // clé fixe — pas de pagination ni filtres
            )
            ->getResult();
    }

    // Pour Get /api/category/{id} si tu l'ajoutes plus tard
    public function findCategoryForApi(int $id): ?Category
    {
        return $this->createQueryBuilder('c')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->enableResultCache(
                2419200,
                sprintf('category_item_%d', $id)
            )
            ->getOneOrNullResult();
    }
    
    public function invalidateForEntity(object $entity): void
    {
        if (!($entity instanceof Category)) return;

        try {
            $this->cacheApiCategories->invalidateTags([
                self::CACHE_CATALOGUE_SHARED_TAG,
                self::CACHE_CATEGORY_TAG,
            ]);
            $this->cacheApiBooks->invalidateTags([
                self::CACHE_CATALOGUE_SHARED_TAG,
                self::CACHE_BOOKS_TAG,
            ]);
        } catch (\Throwable) {
        }
    }
}
