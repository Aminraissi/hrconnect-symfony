<?php

namespace App\Controller;

use App\Entity\OffreEmploi;
use App\Repository\OffreEmploiRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/back-office/candidat/offres-emploi')]
class CandidatOffreEmploiController extends AbstractController
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;
    private Connection $connection;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger, Connection $connection)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->connection = $connection;
    }
    #[Route('/', name: 'back.candidat.offres_emploi.index')]
    public function index(Request $request, OffreEmploiRepository $repository): Response
    {
        $type = $request->query->get('type');
        $searchTerm = $request->query->get('q');

        // Initialiser les offres
        $offres = [];

        // Recherche par terme si fourni
        if ($searchTerm && !empty(trim($searchTerm))) {
            $this->logger->info('Recherche d\'offres par titre: "' . $searchTerm . '"');

            // Recherche directe en SQL pour éviter tout problème
            // Utiliser LOWER pour une recherche insensible à la casse
            $sql = 'SELECT id, title, description, location FROM offre_emploi WHERE LOWER(title) LIKE LOWER(:term)';
            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue('term', '%' . $searchTerm . '%');
            $results = $stmt->executeQuery()->fetchAllAssociative();

            $this->logger->info('Résultats trouvés: ' . count($results));

            // Convertir les résultats en objets OffreEmploi
            foreach ($results as $result) {
                $offre = new OffreEmploi();
                $offre->setId($result['id']);
                $offre->setTitle($result['title']);
                $offre->setDescription($result['description']);
                $offre->setLocation($result['location']);
                $offres[] = $offre;
            }
        }
        // Sinon, afficher toutes les offres
        else {
            $offres = $repository->findAll();
        }

        return $this->render('back_office/candidat/offres_emploi/index.html.twig', [
            'offres' => $offres,
            'type' => $type,
            'searchTerm' => $searchTerm
        ]);
    }

    #[Route('/{id}', name: 'back.candidat.offres_emploi.show')]
    public function show(OffreEmploi $offre): Response
    {
        // Note: isActive n'est plus disponible dans la nouvelle structure
        // Nous ne vérifions donc plus si l'offre est active

        return $this->render('back_office/candidat/offres_emploi/show.html.twig', [
            'offre' => $offre,
        ]);
    }
    #[Route('/advanced-search', name: 'back.candidat.offres_emploi.advanced_search', methods: ['POST'])]
    public function advancedSearch(Request $request, OffreEmploiRepository $repository): Response
    {
        $title = $request->request->get('title');

        $this->logger->info('Recherche avancée d\'offres avec le titre: ' . $title);

        // Créer une requête personnalisée pour la recherche par titre
        $queryBuilder = $repository->createQueryBuilder('o');

        if ($title) {
            $queryBuilder->andWhere('o.title LIKE :title')
                ->setParameter('title', '%' . $title . '%');
        }

        // Exécuter la requête
        $offres = $queryBuilder->getQuery()->getResult();

        $this->logger->info('Résultats de la recherche avancée: ' . count($offres) . ' offres trouvées');

        // Rendre uniquement le contenu du tableau des résultats
        return $this->render('back_office/candidat/offres_emploi/_search_results.html.twig', [
            'offres' => $offres,
        ]);
    }
}