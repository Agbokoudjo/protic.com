<?php

declare(strict_types=1);

namespace App\ApiResource\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Faq;
use App\Repository\FaqRepository;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @implements ProviderInterface<Faq|Faq[]|null>
 */
final class FaqCachedProvider implements ProviderInterface
{
    public function __construct(
        private readonly FaqRepository $faqRepository,
        #[Target('cache.api_faq')]
        private readonly TagAwareCacheInterface $cacheApiFaq,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable|object|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $filters = $context['filters'] ?? [];
            $page    = (int) ($filters['page'] ?? 1);
            $perPage = (int) ($filters['itemsPerPage'] ?? 8);

            // ApiPaginator est retourné directement — pas mis dans Redis
            // car non sérialisable — le cache Doctrine result cache
            // s'occupe des données SQL en interne via enableResultCache()
            return $this->faqRepository->findPublishedForApi($page, $perPage);
        }
        return $this->faqRepository->findPublishedByIdForApi((int) ($uriVariables['id'] ?? 1));
    }

    private function buildCollectionCacheKey(array $context): string
    {
        $filters = $context['filters'] ?? [];
        ksort($filters);
        return sprintf('faq_collection_%s', md5(serialize($filters)));
    }
}
