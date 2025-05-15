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
}
