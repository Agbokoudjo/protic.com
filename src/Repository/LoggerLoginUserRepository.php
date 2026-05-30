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

use App\Entity\LoggerLoginUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use App\Persistance\LoggerLoginUserManagerInterface ;
/**
 * Repository pour les enregistrements de connexion utilisateur.
 *
 * Responsabilités :
 * - Persister les nouveaux enregistrements (append-only)
 * - Fournir des requêtes d'audit (par IP, par utilisateur, par période)
 * - Purger les anciens logs selon une politique de rétention
 *
 * @extends ServiceEntityRepository<LoggerLoginUser>
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
class LoggerLoginUserRepository extends ServiceEntityRepository implements LoggerLoginUserManagerInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoggerLoginUser::class);
    }

    /**
     * Persiste un nouvel enregistrement de connexion depuis le modèle de domaine.
     * Flush immédiat optionnel (par défaut : true pour l'audit temps réel).
     */
    public function save(LoggerLoginUser  $entity, bool $flush = true): LoggerLoginUser
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $entity;
    }

    /**
     * Flush explicite (utile en mode batch).
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * Retourne tous les enregistrements d'un utilisateur (par email).
     *
     * @return LoggerLoginUser[]
     */
    public function findByEmail(string $email): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.email = :email')
            ->setParameter('email', $email)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne tous les enregistrements d'un utilisateur (par nom d'utilisateur).
     *
     * @return LoggerLoginUser[]
     */
    public function findByUsername(string $username): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.username = :username')
            ->setParameter('username', $username)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les enregistrements provenant d'une adresse IP donnée.
     * Utile pour détecter des attaques par force brute depuis une même source.
     *
     * @return LoggerLoginUser[]
     */
    public function findByIp(string $ip): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.lastLoginIp = :ip')
            ->setParameter('ip', $ip)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les enregistrements dans une plage de dates.
     *
     * @return LoggerLoginUser[]
     */
    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de connexions pour un email sur une période glissante.
     * Utile pour détecter des tentatives de brute-force sur un compte.
     */
    public function countByEmailSince(string $email, \DateTimeInterface $since): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.email = :email')
            ->andWhere('l.createdAt >= :since')
            ->setParameter('email', $email)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte le nombre de connexions pour une IP sur une période glissante.
     * Utile pour la détection d'attaques distribuées.
     */
    public function countByIpSince(string $ip, \DateTimeInterface $since): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.lastLoginIp = :ip')
            ->andWhere('l.createdAt >= :since')
            ->setParameter('ip', $ip)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Retourne le dernier enregistrement connu pour un email.
     */
    public function findLastByEmail(string $email): ?LoggerLoginUser
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.email = :email')
            ->setParameter('email', $email)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Supprime (soft-delete via SoftDeleteable) les enregistrements antérieurs
     * à la date de rétention fournie.
     *
     * @param \DateTimeInterface $before  Date limite de rétention
     * @return int                        Nombre d'enregistrements supprimés
     */
    public function purgeOlderThan(\DateTimeInterface $before): int
    {
        // On passe par une mise à jour DQL pour déclencher les événements Doctrine
        // (SoftDeleteable positionne deletedAt au lieu de faire un DELETE physique).
        $qb = $this->createQueryBuilder('l');

        $entities = $qb
            ->andWhere('l.createdAt < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->getResult();

        $em    = $this->getEntityManager();
        $count = 0;

        foreach ($entities as $entity) {
            $em->remove($entity); // SoftDeleteable intercepte et positionne deletedAt
            ++$count;
        }

        $em->flush();

        return $count;
    }

    /**
     * Retourne un QueryBuilder de base pour l'usage Sonata / Paginator.
     */
    public function createAuditQueryBuilder(string $alias = 'l'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->orderBy("{$alias}.createdAt", 'DESC');
    }
}
