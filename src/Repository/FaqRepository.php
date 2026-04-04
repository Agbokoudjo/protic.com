<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Faq;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class FaqRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
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
}
