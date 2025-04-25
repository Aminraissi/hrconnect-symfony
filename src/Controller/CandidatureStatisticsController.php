<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Repository\CandidatureRepository;
use App\Repository\CandidatRepository;
use App\Repository\OffreEmploiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/back-office/candidatures/statistiques')]
class CandidatureStatisticsController extends AbstractController
{
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'back.candidatures.statistics')]
    public function index(
        CandidatureRepository $candidatureRepository,
        CandidatRepository $candidatRepository,
        OffreEmploiRepository $offreRepository
    ): Response {
        $this->logger->info('Affichage des statistiques des candidatures');

        // Statistiques générales
        $totalCandidatures = count($candidatureRepository->findAll());
        $totalCandidats = count($candidatRepository->findAll());
        $totalOffres = count($offreRepository->findAll());

        // Statistiques par statut
        $statusStats = $this->getStatusStatistics($candidatureRepository);

        // Statistiques des scores CV
        $cvScoreStats = $this->getCvScoreStatistics($candidatureRepository);

        // Statistiques par offre d'emploi (top 5 des offres avec le plus de candidatures)
        $offreStats = $this->getOffreStatistics($candidatureRepository);

        // Statistiques mensuelles supprimées

        return $this->render('back_office/candidatures/statistics.html.twig', [
            'totalCandidatures' => $totalCandidatures,
            'totalCandidats' => $totalCandidats,
            'totalOffres' => $totalOffres,
            'statusStats' => $statusStats,
            'cvScoreStats' => $cvScoreStats,
            'offreStats' => $offreStats,
        ]);
    }

    /**
     * Récupère les statistiques par statut
     */
    private function getStatusStatistics(CandidatureRepository $repository): array
    {
        $stats = [
            'en_attente' => 0,
            'En cours' => 0,
            'acceptee' => 0,
            'refusee' => 0,
        ];

        $qb = $this->entityManager->createQueryBuilder();
        $result = $qb->select('c.status, COUNT(c.id) as count')
            ->from(Candidature::class, 'c')
            ->groupBy('c.status')
            ->getQuery()
            ->getResult();

        foreach ($result as $row) {
            $stats[$row['status']] = (int)$row['count'];
        }

        return $stats;
    }

    /**
     * Récupère les statistiques des scores CV
     */
    private function getCvScoreStatistics(CandidatureRepository $repository): array
    {
        // Simuler des statistiques de score CV (à remplacer par une vraie requête si vous avez un champ score)
        $totalCandidatures = count($repository->findAll());

        // Simuler que 60% des candidatures ont un score > 50
        $highScoreCount = (int)($totalCandidatures * 0.6);
        $lowScoreCount = $totalCandidatures - $highScoreCount;

        return [
            'highScore' => $highScoreCount,
            'lowScore' => $lowScoreCount,
            'avgScore' => 65, // Score moyen simulé
            'highScorePercentage' => $totalCandidatures > 0 ? ($highScoreCount / $totalCandidatures) * 100 : 0,
        ];
    }

    /**
     * Récupère les statistiques par offre d'emploi
     */
    private function getOffreStatistics(CandidatureRepository $repository): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $result = $qb->select('o.id, o.title, COUNT(c.id) as count')
            ->from(Candidature::class, 'c')
            ->join('c.offreEmploi', 'o')
            ->groupBy('o.id')
            ->orderBy('count', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $result;
    }

    // Méthode getMonthlyStatistics supprimée
}
