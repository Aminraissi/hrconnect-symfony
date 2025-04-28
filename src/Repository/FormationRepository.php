<?php
namespace App\Repository;

use App\Entity\Formation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Formation>
 */
class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    public function findPaidFormations(): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.price > 0')
            ->getQuery()
            ->getResult();
    }

    public function findFormationsByUserId($userId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT f.*
            FROM formations f
            INNER JOIN formation_participation fp ON f.id = fp.formation_id
            WHERE fp.employe_id = :userId
        ';

        $stmt      = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery(['userId' => $userId]);

        return $resultSet->fetchAllAssociative();
    }

    //    /**
    //     * @return Formation[] Returns an array of Formation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Formation
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
