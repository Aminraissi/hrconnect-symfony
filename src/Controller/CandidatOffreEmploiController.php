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

    #[Route('/recherche-avancee', name: 'back.candidat.offres_emploi.advanced_search', methods: ['GET', 'POST'])]
    public function advancedSearch(Request $request, OffreEmploiRepository $repository): Response
    {
        $this->logger->info('=== DÉBUT DE LA RECHERCHE AVANCÉE ===');
        $this->logger->info('Méthode de la requête: ' . $request->getMethod());
        $this->logger->info('Paramètres POST: ' . json_encode($request->request->all()));
        $this->logger->info('Paramètres GET: ' . json_encode($request->query->all()));

        // Récupérer le paramètre de recherche (d'abord dans GET, puis dans POST si nécessaire)
        $title = $request->query->get('title') ?: $request->request->get('title');
        $this->logger->info('Paramètre title: ' . ($title ?: 'non spécifié'));

        try {
            // Créer une requête personnalisée avec QueryBuilder
            $queryBuilder = $repository->createQueryBuilder('o');

            // Ajouter la condition de recherche par titre
            if ($title && !empty(trim($title))) {
                $queryBuilder->andWhere('LOWER(o.title) LIKE LOWER(:title)')
                    ->setParameter('title', '%' . trim($title) . '%');
                $this->logger->info('Condition de recherche ajoutée: title LIKE %' . trim($title) . '%');
            } else {
                $this->logger->info('Aucune condition de recherche ajoutée (titre vide ou non spécifié)');
            }

            // Exécuter la requête
            $offres = $queryBuilder->getQuery()->getResult();
            $this->logger->info('Résultats de la recherche: ' . count($offres) . ' offres trouvées');

            // Vérifier si c'est une requête Ajax ou un appel direct
            $isAjax = $request->isXmlHttpRequest() || $request->headers->get('Accept') === 'text/plain';
            $this->logger->info('Type de requête: ' . ($isAjax ? 'Ajax' : 'Direct'));

            if ($isAjax) {
                // Rendre uniquement le contenu du tableau des résultats pour Ajax
                $html = $this->renderView('back_office/candidat/offres_emploi/_search_results.html.twig', [
                    'offres' => $offres,
                    'searchTerm' => $title
                ]);
                
                $this->logger->info('Template partiel rendu avec succès');
                $this->logger->info('Taille du HTML généré: ' . strlen($html) . ' caractères');
                $this->logger->info('=== FIN DE LA RECHERCHE AVANCÉE (AJAX) ===');
                
                return new Response($html);
            } else {
                // Rendre la page complète pour un appel direct
                $this->logger->info('Rendu de la page complète');
                $this->logger->info('=== FIN DE LA RECHERCHE AVANCÉE (PAGE COMPLÈTE) ===');
                
                return $this->render('back_office/candidat/offres_emploi/index.html.twig', [
                    'offres' => $offres,
                    'searchTerm' => $title
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('ERREUR lors de la recherche: ' . $e->getMessage());
            $this->logger->error('Trace: ' . $e->getTraceAsString());
            $this->logger->info('=== FIN DE LA RECHERCHE AVANCÉE (AVEC ERREUR) ===');
            
            return new Response('Erreur lors de la recherche: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'back.candidat.offres_emploi.show', requirements: ['id' => '\d+'])]
    public function show(OffreEmploi $offre): Response
    {
        // Note: isActive n'est plus disponible dans la nouvelle structure
        // Nous ne vérifions donc plus si l'offre est active

        return $this->render('back_office/candidat/offres_emploi/show.html.twig', [
            'offre' => $offre,
        ]);
    }
}
