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

use App\Entity\SonataUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use App\Domain\User\Model\BaseUserInterface;
use Doctrine\ORM\QueryBuilder ;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
final class SonataUserRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        #[Target('data.respository.cache')]
        private readonly  TagAwareCacheInterface $dataCacheUser,
       ){
        parent::__construct($managerRegistry, SonataUser::class);
    }

    /**
     * Récupère le QueryBuilder pour les utilisateurs actifs n'ayant AUCUN des rôles spécifiés.
     * * @param array<string> $exclude_roles Rôles à exclure (ex: ['ROLE_FOUNDER','ROLE_SUPER_ADMIN'])
     */
    protected function createQueryBuilderForEnabledUsersExcludingRoles(
        array $exclude_roles = ['ROLE_FOUNDER', 'ROLE_SUPER_ADMIN']
    ): QueryBuilder {

        $qb = $this->createQueryBuilder('u')
            ->where('u.enabled = :enabled')
            ->setParameter('enabled', true);

        $andConditions = $qb->expr()->andX();
        foreach ($exclude_roles as $index => $role) {
            $paramName = 'excluded_role_' . $index;
            // JSONB_EXISTS(u.roles, :role) = false → rôle n'existe PAS
            $andConditions->add(sprintf('JSONB_EXISTS(u.roles, :%s) = false', $paramName));
            $qb->setParameter($paramName, $role);
        }

        $qb->andWhere($andConditions);
        return $qb;
    }

    /**
     * Récupère un tableau associatif d'utilisateurs actifs n'ayant AUCUN des rôles spécifiés, 
     * formaté pour un champ de formulaire : ['username' => 'id'].
     *
     * @param array<string> $exclude_roles Rôles à exclure (par défaut: ['ROLE_FOUNDER']).
     * @return array<string, int> Tableau [Username => ID].
     */
    public function findByUserWithExcludRole(array $exclude_roles = ['ROLE_FOUNDER', 'ROLE_SUPER_ADMIN']): array
    {
        $cache_key_admin_user = join("_", $exclude_roles) . '_';
        return $this->dataCacheUser->get($cache_key_admin_user, function (ItemInterface $item) use ($exclude_roles): array {
            $item->tag(['admin_user_cache']);

            $queryBuilder = $this->createQueryBuilderForEnabledUsersExcludingRoles($exclude_roles);
            $users = $queryBuilder->getQuery()->getResult();

            $userChoices = [];

            /** @var BaseUserInterface $user */
            foreach ($users as $user) {
                $userChoices[$user->getUsername()] = $user->getId();
            }
            return $userChoices;
        });
    }


    public function invalidateCache(): void
    {
        try {
            $this->dataCacheUser->invalidateTags(['admin_user_cache']);
        } catch (\InvalidArgumentException $e) {
            return; //on laisse silencieusement l'exception pour ne pas bloquer l'application
        }
    }
}
