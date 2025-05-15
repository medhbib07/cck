<?php

namespace App\Repository;

use App\Entity\Universite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SebastianBergmann\CodeCoverage\Report\Xml\Unit;

/**
 * @extends ServiceEntityRepository<UniversiteRepository>
 */
class UniversiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Universite::class);
    }

 public function findWithFilters(string $search, string $city, string $etype): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.etablissements', 'e');

        if ($search) {
            $qb->andWhere('u.nom LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        if ($city) {
            $qb->andWhere('e.ville = :city')
               ->setParameter('city', $city);
        }
        if ($etype) {
            $qb->andWhere('e.etype = :etype')
               ->setParameter('etype', $etype);
        }

        return $qb->getQuery()->getResult();
    }

    public function findWithFiltersAndPagination(string $search, string $city, string $etype, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.etablissements', 'e');

        if ($search) {
            $qb->andWhere('u.nom LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        if ($city) {
            $qb->andWhere('e.ville = :city')
               ->setParameter('city', $city);
        }
        if ($etype) {
            $qb->andWhere('e.etype = :etype')
               ->setParameter('etype', $etype);
        }

        return $qb->setFirstResult(($page - 1) * $limit)
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    public function countWithFilters(string $search, string $city, string $etype): int
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
            ->leftJoin('u.etablissements', 'e');

        if ($search) {
            $qb->andWhere('u.nom LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        if ($city) {
            $qb->andWhere('e.ville = :city')
               ->setParameter('city', $city);
        }
        if ($etype) {
            $qb->andWhere('e.etype = :etype')
               ->setParameter('etype', $etype);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
