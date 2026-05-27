<?php

declare(strict_types=1);

namespace App\Repository;

// Pour les entités de contenu (FAQ, GlobalSetting, etc.)
interface ApiCacheInvalidatorForContentInterface extends ApiCacheInvalidatorInterface
{
    public const CACHE_CONTENT_SHARED_TAG = 'content';
    public const CACHE_FAQ_TAG            = 'faq_list';
}
