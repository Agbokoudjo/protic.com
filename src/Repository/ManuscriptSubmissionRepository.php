<?php

namespace App\Repository;

use App\Entity\ManuscriptSubmission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ManuscriptSubmission>
 */
class ManuscriptSubmissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ManuscriptSubmission::class);
    }

    public function add(ManuscriptSubmission $entity ,bool $flush=true):void{

        $this->getEntityManager()->persist($entity);

        if($flush){
            $this->getEntityManager()->flush() ;
        }
    }
}
