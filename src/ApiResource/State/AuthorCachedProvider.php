<?php

declare(strict_types=1);

namespace App\ApiResource\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\AuthorRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @implements ProviderInterface<mixed>
 */
final class AuthorCachedProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider', lazy: true)]
        private readonly ProviderInterface $itemProvider,

        private readonly AuthorRepository $authorRepository,

        #[Target("cache.api_authors")]
        private readonly TagAwareCacheInterface $cacheApiAuthors,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable|object|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $cacheKey = $this->buildCollectionCacheKey($context);

            return $this->cacheApiAuthors->get(
                $cacheKey,
                function (ItemInterface $item) use ($context) {
                    $item->expiresAfter(1209600);
                    $item->tag([
                        AuthorRepository::CACHE_CATALOGUE_SHARED_TAG,
                        AuthorRepository::CACHE_AUTHOR_TAG,
                    ]);

                    $filters = $context['filters'] ?? [];
                    $page    = (int) ($filters['page'] ?? 1);
                    $perPage = (int) ($filters['itemsPerPage'] ?? 10);

                    return $this->authorRepository->findAuthorsForApi($page, $perPage);
                }
            );
        }

        // Get /api/authors/{id}
        $id       = (int) ($uriVariables['id'] ?? 0);
        $cacheKey = sprintf('author_item_%d', $id);

        return $this->cacheApiAuthors->get(
            $cacheKey,
            function (ItemInterface $item) use ($operation, $uriVariables, $context) {
                $item->expiresAfter(1209600);
                $item->tag([
                    AuthorRepository::CACHE_CATALOGUE_SHARED_TAG,
                    AuthorRepository::CACHE_AUTHOR_TAG,
                ]);
                return $this->itemProvider->provide($operation, $uriVariables, $context);
            }
        );
    }

    private function buildCollectionCacheKey(array $context): string
    {
        $filters = $context['filters'] ?? [];
        ksort($filters);
        return sprintf('authors_collection_%s', md5(serialize($filters)));
    }
}
