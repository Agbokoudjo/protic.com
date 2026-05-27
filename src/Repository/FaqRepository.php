<?php

declare(strict_types=1);

namespace App\Repository;

use ApiPlatform\State\Pagination\TraversablePaginator;
use App\Entity\Faq;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @extends ServiceEntityRepository<Faq>
 */
final class FaqRepository extends ServiceEntityRepository implements ApiCacheInvalidatorForContentInterface
{
    public function __construct(
        ManagerRegistry $registry,
        #[Target('cache.api_faq')]
        private readonly TagAwareCacheInterface $cacheApiFaq,
        #[Target('doctrine.result_cache.faq')]   // pool Doctrine isolé pour FAQ
        private readonly CacheItemPoolInterface $faqDoctrineCache,
    ) {
        parent::__construct($registry, Faq::class);
    }

    /**
     * Retourne toutes les FAQ publiées, triées par position puis date
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.published = true')
            ->andWhere('f.deletedAt IS NULL')
            ->andWhere('f.answer IS NOT NULL')
            ->orderBy('f.position', 'ASC')
            ->addOrderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * FAQ publiées par catégorie
     */
    public function findPublishedByCategory(string $category): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.published = true')
            ->andWhere('f.deletedAt IS NULL')
            ->andWhere('f.category = :cat')
            ->setParameter('cat', $category)
            ->orderBy('f.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPublishedForApi(int $page, int $perPage): TraversablePaginator
    {
        $firstResult = ($page - 1) * $perPage;

        // Requête COUNT — clé fixe, invalidée séparément
        $total = (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.published = :published')
            ->andWhere('f.answer IS NOT NULL')
            ->setParameter('published', true)
            ->getQuery()
            ->setResultCache($this->faqDoctrineCache) // ← pool isolé
            ->enableResultCache(604800, 'faq_published_count')
            ->getSingleScalarResult();

        // Requête données — clé par page
        $items = $this->createQueryBuilder('f')
            ->where('f.published = :published')
            ->andWhere('f.answer IS NOT NULL')
            ->setParameter('published', true)
            ->orderBy('f.position', 'ASC')
            ->addOrderBy('f.createdAt', 'DESC')
            ->setMaxResults($perPage)
            ->setFirstResult($firstResult)
            ->getQuery()
            ->setResultCache($this->faqDoctrineCache) // ← pool isolé
            ->enableResultCache(
                604800,
                sprintf('faq_published_page_%d_per_%d', $page, $perPage)
            )
            ->getResult();

        return new TraversablePaginator(
            new \ArrayIterator($items),
            $page,       // currentPage
            $perPage,    // itemsPerPage
            $total       // totalItems ← COUNT mis en cache 
        );
    }

    /**
     * Détail d'une FAQ publiée par ID
     * Retourne null si non publiée → API Platform génère automatiquement un 404
     */
    public function findPublishedByIdForApi(int $id): ?Faq
    {
        try {
            $cacheKey = sprintf('faq_item_%d', $id);
            return $this->cacheApiFaq->get(
                $cacheKey,
                function (ItemInterface $item) use ($id) {
                    $item->expiresAfter(604800);
                    $item->tag([
                        FaqRepository::CACHE_CONTENT_SHARED_TAG,
                        FaqRepository::CACHE_FAQ_TAG,
                    ]);

                    return $this->createQueryBuilder('f')
                        ->where('f.id = :id')
                        ->andWhere('f.published = :published')
                        ->andWhere('f.answer IS NOT NULL')
                        ->setParameter('id', $id)
                        ->setParameter('published', true)
                        ->getQuery()
                        ->setResultCache($this->faqDoctrineCache)
                        ->enableResultCache(
                            604800,
                            sprintf('faq_published_item_%d', $id)
                        )
                        ->getOneOrNullResult();
                }
            );

        } catch (\Throwable $th) {
            return null ;
        }
    }

    public function invalidateForEntity(object $entity): void
    {
        if (!($entity instanceof Faq)) return;

        try {
            $this->cacheApiFaq->invalidateTags([
                self::CACHE_CONTENT_SHARED_TAG,
                self::CACHE_FAQ_TAG,
            ]);
            $cacheKey = sprintf('faq_item_%d', $entity->getId());
            $this->$this->cacheApiFaq->delete($cacheKey);
            // Pool Doctrine FAQ → COUNT + pages + items
            // clear() ici est safe car le pool est ISOLÉ pour FAQ uniquement
            $this->faqDoctrineCache->clear();
        } catch (\Throwable) {
            // Redis indisponible → silencieux
        }
    }
}
