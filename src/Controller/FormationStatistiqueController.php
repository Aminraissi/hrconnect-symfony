<?php
namespace App\Controller;

use App\Repository\QuizReponseRepository;
use App\Repository\QuizRepository;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FormationStatistiqueController extends AbstractController
{
    #[Route('/backoffice/quiz-repartition-reponses-statistique/{id}', name: 'app_quiz_repartition_rep_statistique')]
    public function RepartitionDesReponses(QuizReponseRepository $quizReponseRepository, QuizRepository $quizRepository, $id): Response
    {
        $stats = $quizReponseRepository->getReponseStatsForQuiz($id);

        $quiz = $quizRepository->find($id);

        $data = [['Numéro de Réponse', 'Nombre d\'Utilisateurs']];

        foreach ($stats as $stat) {
            if ($stat['numReponse'] == 1) {
                $reponse = $quiz->getReponse1();
            } else if ($stat['numReponse'] == 2) {
                $reponse = $quiz->getReponse2();
            } else {
                $reponse = $quiz->getReponse3();
            }
            $data[] = [(string) $stat['numReponse'] . $reponse, $stat['count']];
        }

        $pieChart = new PieChart();
        $pieChart->getData()->setArrayToDataTable($data);
        $pieChart->getOptions()->setTitle('Répartition des Réponses');
        $pieChart->getOptions()->setHeight(400);
        $pieChart->getOptions()->setWidth(600);

        if ($quiz->getNum_reponse_correct() == 1) {
            $bonneReponse = $quiz->getReponse1();
        } else if ($quiz->getNum_reponse_correct() == 2) {
            $bonneReponse = $quiz->getReponse2();
        } else {
            $bonneReponse = $quiz->getReponse3();
        }

        return $this->render('formations/stats/reponse_repartition.html.twig', [
            'pieChart'     => $pieChart,
            'quiz'         => $quiz,
            'bonneReponse' => $bonneReponse,
        ]);
    }
}
