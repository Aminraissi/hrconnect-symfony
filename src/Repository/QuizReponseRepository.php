<?php
namespace App\Repository;

use App\Entity\Quiz;
use App\Entity\QuizReponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quiz>
 */
class QuizReponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuizReponse::class);
    }

    public function getReponseStatsForQuiz(int $quizId)
    {
        // Sélectionne toutes les réponses pour un quiz donné
        $qb = $this->createQueryBuilder('qr')
            ->select('qr.numReponse, COUNT(qr.numReponse) as count')
            ->where('qr.quiz = :quizId')
            ->setParameter('quizId', $quizId)
            ->groupBy('qr.numReponse')
            ->orderBy('qr.numReponse', 'ASC');

        $result = $qb->getQuery()->getResult();

        // Calcul du total des réponses pour calculer les pourcentages
        $totalReponses = array_sum(array_column($result, 'count'));

        $stats = [];
        foreach ($result as $row) {
            $stats[] = [
                'numReponse' => $row['numReponse'],
                'percentage' => ($row['count'] / $totalReponses) * 100, // Calcul du pourcentage
                'count'      => $row['count'],
            ];
        }

        return $stats;
    }

}
