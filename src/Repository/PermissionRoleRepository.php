<?php

declare(strict_types=1);
/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * Phone: +229 01 67 25 18 86
 * LinkedIn: https://www.linkedin.com/in/internationales-web-apps-services-120520193/
 * Github: https://github.com/Agbokoudjo/
 * Company: INTERNATIONALES WEB APPS & SERVICES
 *
 * For more information, please feel free to contact the author.
 */

namespace App\Repository;

use App\Entity\BaseUserInterface;
use App\Entity\PermissionRole;
use App\Infrastructure\Doctrine\Entity\Security\PermissionRoleEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
final class PermissionRoleRepository extends ServiceEntityRepository 
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        #[Target('data.respository.cache')]
        private readonly  TagAwareCacheInterface $tagAwareCache
        )
    {
        parent::__construct($managerRegistry, PermissionRole::class);
    }


    /**
     * Récupère le QueryBuilder des rôles créés par un administrateur (pour EntityType).
     * 
     * @param  BaseUserInterface $adminUser
     * @return QueryBuilder
     */
    public function getPermissionRoleCreatedByUserQueryBuilder(BaseUserInterface $adminUser): QueryBuilder
    {
        return $this->createQueryBuilder('pr')
            ->select('pr')
            ->where('pr.createdBy = :admin_user')
            ->setParameter('admin_user', $adminUser)
            ->orderBy('pr.id', 'DESC');
    }

    /**
     * Récupère les rôles créés par un administrateur uniquement.
     * 
     * @param  BaseUserInterface $adminUser
     * @return array<PermissionRoleEntity>
     */
    public function getPermissionRoleCreatedByUser(BaseUserInterface $adminUser): array
    {
        $permission_cache_key = $this->permissionCacheKey($adminUser, 'user_permission_role_created_by_role_user');
        
        return $this->tagAwareCache->get(
            $permission_cache_key,
            function (ItemInterface $item) use ($adminUser): array {
                $item->tag(['permission_role_' . $adminUser->getId()]);

                $permission_role_data = $this->createQueryBuilder('pr')
                    ->select('pr')  
                    ->where('pr.createdBy = :admin_user')  // Utiliser where avec paramètre
                    ->setParameter('admin_user', $adminUser)
                    ->orderBy('pr.id', 'DESC')
                    ->getQuery()
                    ->getResult();
                return $permission_role_data ?? [];
            }
        );
    }

    /**
     * Alternative avec innerJoin si vous avez besoin de plus de données
     * @return array<PermissionRoleEntity>
     */
    public function getPermissionRoleCreatedByUserWithJoin(BaseUserInterface $adminUser): array
    {
        $permission_cache_key = $this->permissionCacheKey($adminUser, 'user_permission_role_inner_with_join');

        return $this->tagAwareCache->get(
            $permission_cache_key,
            function (ItemInterface $item) use ($adminUser): array {
                $item->tag(['permission_role_' . $adminUser->getId()]);

                $permission_role_data = $this->createQueryBuilder('pr')
                    ->select('pr.name', 'pr.id', 'pr.description', 'pr.context')
                    ->innerJoin('pr.createdBy', 'admin_user')
                    ->where('admin_user.id = :admin_id')
                    ->setParameter('admin_id', $adminUser->getId())
                    ->orderBy('pr.id', 'DESC')
                    ->getQuery()
                    ->getResult();

                return $permission_role_data ?? [];
            }
        );
    }

    /**
     * Récupère les rôles créés par un administrateur indexé par ID
     * @return array<PermissionRoleEntity>
     */
    public function getPermissionRoleCreatedByUserIndexed(BaseUserInterface $adminUser): array
    {
        $permission_cache_key = $this->permissionCacheKey($adminUser, 'user_permission_role_indexed');

        return $this->tagAwareCache->get(
            $permission_cache_key,
            function (ItemInterface $item) use ($adminUser): array {
                $item->tag(['permission_role_' . $adminUser->getId()]);

                $results = $this->createQueryBuilder('pr')
                    ->select('pr')
                    ->where('pr.createdBy = :admin_user')
                    ->setParameter('admin_user', $adminUser)
                    ->indexBy('pr', 'pr.id')  // Index par ID
                    ->getQuery()
                    ->getResult();

                return $results ?? [];
            }
        );
    }

    /**
     * Génère la clé de cache pour un administrateur
     * 
     * @param BaseUserInterface $adminUser
     * @param string $context
     * @return string
     */
    private function permissionCacheKey(
        BaseUserInterface $adminUser,
        string $context = "user_permission_role"
    ): string {
        $suffix_key = (string)$adminUser->getId() . '_' . $adminUser->getEmailCanonical();
        return "cache_" . $context . "_" . $suffix_key;
    }


    /**
     * Vider le cache pour un utilisateur spécifique
     * À appeler quand on crée/modifie/supprime un rôle
     */
    public function clearCacheForUser(BaseUserInterface $adminUser): void
    {
        try {
            $this->tagAwareCache->invalidateTags(['permission_role_' . $adminUser->getId()]);
        } catch (\InvalidArgumentException $InvalidTagsException) {
            return; //on ne fait rien 
        }
    }
}
