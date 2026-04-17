<?php

declare(strict_types=1);

/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * For more information, please feel free to contact the author.
 */

namespace App\Repository;

use App\Entity\TeamMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @extends ServiceEntityRepository<TeamMember>
 */
final class TeamMemberRepository extends ServiceEntityRepository
{
    private const CACHE_TAG = 'team_members_list';

    public function __construct(
        ManagerRegistry $registry,
        #[Target('data.respository.cache')]
        private readonly TagAwareCacheInterface $dataCacheTeamMember
    ) {
        parent::__construct($registry, TeamMember::class);
    }

    public function add(TeamMember $entity, bool $flush = true): void
    {

        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        $this->invalidateCacheTeamMember();
    }

    /**
     * Retourne tous les membres visibles, triés par position ASC.
     * Utilisé côté front (page About).
     *
     * @return TeamMember[]
     */
    /**
     * Retourne tous les membres visibles avec mise en cache.
     */
    public function findVisibleOrderedByPosition(): array
    {
        return $this->dataCacheTeamMember->get('team_visible_ordered', function (ItemInterface $item) {
            // On définit le tag pour pouvoir l'invalider plus tard
            $item->tag([self::CACHE_TAG]);
            $item->expiresAfter(604800); // Expire après 7j par sécurité

            return $this->createQueryBuilder('tm')
                ->andWhere('tm.visible = :visible')
                ->setParameter('visible', true)
                ->orderBy('tm.position', 'ASC')
                ->getQuery()
                ->getResult();
        });
    }

    /**
     * Méthode pour invalider le cache
     */
    public function invalidateCacheTeamMember(): void
    {
        try {
            $this->dataCacheTeamMember->invalidateTags([self::CACHE_TAG]);
            $this->dataCacheTeamMember->delete('team_visible_ordered');
        } catch (\Throwable $th) {
            
        }
    }
}
