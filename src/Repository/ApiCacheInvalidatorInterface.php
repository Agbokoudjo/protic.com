<?php
declare(strict_types=1);

namespace App\Repository;

interface ApiCacheInvalidatorInterface 
{
    public function invalidateForEntity(object $entity): void ;
}
