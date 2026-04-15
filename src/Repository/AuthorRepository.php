<?php

namespace App\Repository;

use App\Entity\Author;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Author>
 */
class AuthorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Author::class);
    }

    public function findAllForSitemap(int $batchSize = 100): \Generator
    {
        $offset = 0;
        do {
            $results = $this->createQueryBuilder('a')
                ->select('partial a.{id, updatedAt}')
                ->orderBy('a.id', 'ASC')
                ->setMaxResults($batchSize)
                ->setFirstResult($offset)
                ->getQuery()
                ->toIterable();

            $count = 0;
            foreach ($results as $author) {
                yield $author;
                $count++;
            }

            $offset += $batchSize;
        } while ($count === $batchSize);
    }
    
    //    /**
    //     * @return Author[] Returns an array of Author objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Author
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
