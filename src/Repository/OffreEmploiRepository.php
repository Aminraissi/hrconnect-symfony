<?php
namespace App\Repository;

use App\Entity\OffreEmploi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OffreEmploi>
 */
class OffreEmploiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OffreEmploi::class);
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByTypeContrat(string $typeContrat): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
