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
                'e.ville as ville'
            ])
            ->where('e.groupe = :universite')
            ->setParameter('public', Etablissement::ETYPE_PUBLIC)
            ->setParameter('private', Etablissement::ETYPE_PRIVATE)
            ->setParameter('universite', $universite)
            ->groupBy('e.ville');
    
        $results = $queryBuilder->getQuery()->getResult();
    
        // Prepare data for charts
        $locationData = [];
        foreach ($results as $result) {
            $locationData[] = [
                'location' => $result['ville'],
                'count' => $result['total']
            ];
        }
    // Chart 2: Students per establishment
   $studentQuery = $this->getEntityManager()->createQueryBuilder()
    ->select('e.nom as name, COUNT(s.id) as students')
    ->from(Etablissement::class, 'e')
    ->leftJoin('e.etudiants', 's') // assuming Etablissement has OneToMany 'etudiants'
    ->where('e.groupe = :universite')
    ->groupBy('e.id')
    ->setParameter('universite', $universite)
    ->getQuery()
    ->getResult();


    $studentsPerEtablissement = [];
    foreach ($studentQuery as $row) {
        $studentsPerEtablissement[] = [
            'name' => $row['name'],
            'students' => $row['students']
        ];
    }

    // Chart 3: Total students by type
   $studentTypeQuery = $this->getEntityManager()->createQueryBuilder()
    ->select('e.etype, COUNT(s.id) as count')
    ->from(Etablissement::class, 'e')
    ->leftJoin('e.etudiants', 's')
    ->where('e.groupe = :universite')
    ->groupBy('e.etype')
    ->setParameter('universite', $universite)
    ->getQuery()
    ->getResult();

// Normalize results
$studentTypeData = ['public' => 0, 'private' => 0];
foreach ($studentTypeQuery as $row) {
    if ((int)$row['etype'] === Etablissement::ETYPE_PUBLIC) {
        $studentTypeData['public'] = $row['count'];
    } elseif ((int)$row['etype'] === Etablissement::ETYPE_PRIVATE) {
        $studentTypeData['private'] = $row['count'];
    }
}


    return [
        'total' => array_sum(array_column($results, 'total')),
        'public_count' => array_sum(array_column($results, 'public_count')),
        'private_count' => array_sum(array_column($results, 'private_count')),
        'location_data' => $locationData,
        'students_per_etablissement' => $studentsPerEtablissement,
        'students_by_type' => [
            'Publique' => (int) $studentTypeData['public'],
            'Privé' => (int) $studentTypeData['private'],
        ]
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
