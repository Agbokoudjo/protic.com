<?php

declare(strict_types=1);

namespace App\ApiResource\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\CategoryRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @implements ProviderInterface<mixed>
 */
final class CategoryCachedProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider', lazy: true)]
        private readonly ProviderInterface $itemProvider,

        private readonly CategoryRepository $categoryRepository,

        #[Target("cache.api_categories")]
        private readonly TagAwareCacheInterface $cacheApiCategories,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable|object|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            // Clé fixe — pas de pagination ni filtres sur Category
            $cacheKey = 'categories_collection_all';

            return $this->cacheApiCategories->get(
                $cacheKey,
                function (ItemInterface $item) {
                    $item->expiresAfter(2419200);
                    $item->tag([
                        CategoryRepository::CACHE_CATALOGUE_SHARED_TAG,
                        CategoryRepository::CACHE_CATEGORY_TAG,
                    ]);
                    return $this->categoryRepository->findCategoriesForApi();
                }
            );
        }

        // Get /api/category/{id} — pour usage futur
        $id       = (int) ($uriVariables['id'] ?? 0);
        $cacheKey = sprintf('category_item_%d', $id);

        return $this->cacheApiCategories->get(
            $cacheKey,
            function (ItemInterface $item) use ($operation, $uriVariables, $context) {
                $item->expiresAfter(2419200);
                $item->tag([
                    CategoryRepository::CACHE_CATALOGUE_SHARED_TAG,
                    CategoryRepository::CACHE_CATEGORY_TAG,
                ]);
                return $this->itemProvider->provide($operation, $uriVariables, $context);
            }
        );
    }
}
