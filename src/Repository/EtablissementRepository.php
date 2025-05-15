<?php

namespace App\Repository;

use App\Entity\Etablissement;
use App\Entity\Universite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Etablissement>
 */
class EtablissementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Etablissement::class);
    }

    public function getEstablishmentStats(Universite $universite): array
    {
        $queryBuilder = $this->createQueryBuilder('e')
            ->select([
                'COUNT(e.id) as total',
                'SUM(CASE WHEN e.etype = :public THEN 1 ELSE 0 END) as public_count',
                'SUM(CASE WHEN e.etype = :private THEN 1 ELSE 0 END) as private_count',
                'e.localisation as location'
            ])
            ->where('e.groupe = :universite')
            ->setParameter('public', Etablissement::ETYPE_PUBLIC)
            ->setParameter('private', Etablissement::ETYPE_PRIVATE)
            ->setParameter('universite', $universite)
            ->groupBy('e.localisation');
    
        $results = $queryBuilder->getQuery()->getResult();
    
        // Prepare data for charts
        $locationData = [];
        foreach ($results as $result) {
            $locationData[] = [
                'location' => $result['location'],
                'count' => $result['total']
            ];
        }
    
        return [
            'total' => array_sum(array_column($results, 'total')),
            'public_count' => array_sum(array_column($results, 'public_count')),
            'private_count' => array_sum(array_column($results, 'private_count')),
            'location_data' => $locationData,
        ];
    }
    public function findWithFilters(string $search, string $universite, string $city, string $etype): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.groupe', 'u');

        if ($search) {
            $qb->andWhere('e.nom LIKE :search OR e.ville LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        if ($universite) {
            $qb->andWhere('u.nom = :universite')
               ->setParameter('universite', $universite);
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

    public function findWithFiltersAndPagination(string $search, string $universite, string $city, string $etype, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.groupe', 'u');

        if ($search) {
            $qb->andWhere('e.nom LIKE :search OR e.ville LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        if ($universite) {
            $qb->andWhere('u.nom = :universite')
               ->setParameter('universite', $universite);
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

    public function countWithFilters(string $search, string $universite, string $city, string $etype): int
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->leftJoin('e.groupe', 'u');

        if ($search) {
            $qb->andWhere('e.nom LIKE :search OR e.ville LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        if ($universite) {
            $qb->andWhere('u.nom = :universite')
               ->setParameter('universite', $universite);
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
