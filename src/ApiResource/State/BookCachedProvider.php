<?php

declare(strict_types=1);

// src/ApiResource/State/BookCachedProvider.php
namespace App\ApiResource\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\BookRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @implements ProviderInterface<mixed>
 */
final class BookCachedProvider implements ProviderInterface
{
    public function __construct(
        // Provider Doctrine natif pour un item
        #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider', lazy: true)]
        private readonly ProviderInterface $itemProvider,
        private readonly BookRepository $bookRepository,
        #[Target("cache.api_books")]
        private readonly TagAwareCacheInterface $cacheApiBooks
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable|object|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $filters  = $context['filters'] ?? [];
            $page     = (int) ($filters['page'] ?? 1);
            $perPage  = (int) ($filters['itemsPerPage'] ?? 12);

            // TraversablePaginator retourné directement
            // API Platform génère hydra:totalItems + hydra:view automatiquement 
            return $this->bookRepository->findBooksForApi($page, $perPage);
        }

        // Get /api/books/{id} — inchangé
        $cacheKey = sprintf('book_item_%s', $uriVariables['id'] ?? 'unknown');

        return $this->cacheApiBooks->get(
            $cacheKey,
            function (ItemInterface $item) use ($operation, $uriVariables, $context) {
                $item->expiresAfter(604800);
                $item->tag([
                    BookRepository::CACHE_CATALOGUE_SHARED_TAG,
                    BookRepository::CACHE_BOOKS_TAG,
                ]);
                return $this->itemProvider->provide($operation, $uriVariables, $context);
            }
        );
    }

    private function buildCollectionCacheKey(array $context): string
    {
        // On inclut les filtres actifs dans la clé
        // ex: books_page_2_order_title_ASC_category_roman
        $filters = $context['filters'] ?? [];
        ksort($filters);

        return sprintf('books_collection_%s', md5(serialize($filters)));
    }
}

// Provider Doctrine natif pour les collections
        // #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider', lazy: true)]
        // private readonly ProviderInterface $collectionProvider,