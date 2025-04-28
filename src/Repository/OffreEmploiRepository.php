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
        // Note: isActive n'est plus un champ mappé dans la base de données
        // Nous récupérons donc toutes les offres sans filtrage
        return $this->createQueryBuilder('o')
            ->orderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByTypeContrat(string $typeContrat): array
    {
        // Note: typeContrat et isActive ne sont plus des champs mappés dans la base de données
        // Nous récupérons donc toutes les offres sans filtrage
        return $this->createQueryBuilder('o')
            ->orderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
