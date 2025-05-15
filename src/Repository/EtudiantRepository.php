<?php

namespace App\Repository;

use App\Entity\Etudiant;
use App\Entity\Etablissement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EtudiantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Etudiant::class);
    }

    public function findByEtablissementWithSearchAndPagination(Etablissement $etablissement, string $search, int $page, int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('e')
            ->where('e.etablissement = :etablissement')
            ->setParameter('etablissement', $etablissement)
            ->orderBy('e.nom', 'ASC');

        if ($search) {
            $queryBuilder->andWhere('e.nom LIKE :search OR e.prenom LIKE :search OR e.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByEtablissementWithSearch(Etablissement $etablissement, string $search): int
    {
        $queryBuilder = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.etablissement = :etablissement')
            ->setParameter('etablissement', $etablissement);

        if ($search) {
            $queryBuilder->andWhere('e.nom LIKE :search OR e.prenom LIKE :search OR e.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}