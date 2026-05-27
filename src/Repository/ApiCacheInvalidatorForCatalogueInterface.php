<?php

declare(strict_types=1);

namespace App\Repository;
//pour cattalogue(book,author,category)
interface ApiCacheInvalidatorForCatalogueInterface extends ApiCacheInvalidatorInterface
{
    public const CACHE_CATALOGUE_SHARED_TAG ="catalogue" ;
    public const CACHE_BOOKS_TAG = "books_list";
    public const CACHE_AUTHOR_TAG = "author_list";
    public const CACHE_CATEGORY_TAG = "category_list";
}
