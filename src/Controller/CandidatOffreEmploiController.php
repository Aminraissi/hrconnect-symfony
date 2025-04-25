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
    public function index(OffreEmploiRepository $repository): Response
    {
        // Récupérer toutes les offres
        $offres = $repository->findAll();

        return $this->render('back_office/candidat/offres_emploi/index.html.twig', [
            'offres' => $offres
        ]);
    }

    #[Route('/recherche', name: 'back.candidat.offres_emploi.search')]
    public function search(OffreEmploiRepository $repository): Response
    {
        // Récupérer toutes les offres pour l'affichage initial
        $offres = $repository->findAll();

        // Rendre la page de recherche indépendante
        return $this->render('back_office/candidat/offres_emploi/search.html.twig', [
            'offres' => $offres
        ]);
    }

    #[Route('/recherche-avancee', name: 'back.candidat.offres_emploi.advanced_search', methods: ['GET'])]
    public function advancedSearch(Request $request, OffreEmploiRepository $repository): Response
    {
        $this->logger->info('=== DÉBUT DE LA RECHERCHE AVANCÉE ===');

        // Récupérer le paramètre de recherche
        $title = $request->query->get('title');
        $this->logger->info('Terme de recherche: "' . ($title ?: '') . '"');

        try {
            // Créer une requête personnalisée avec QueryBuilder
            $queryBuilder = $repository->createQueryBuilder('o');

            // Ajouter la condition de recherche par titre
            if ($title && !empty(trim($title))) {
                $queryBuilder->andWhere('LOWER(o.title) LIKE LOWER(:title)')
                    ->setParameter('title', '%' . trim($title) . '%');
            }

            // Exécuter la requête
            $offres = $queryBuilder->getQuery()->getResult();
            $this->logger->info('Résultats: ' . count($offres) . ' offres trouvées');

            // Générer le HTML pour les résultats
            $html = '';

            if (count($offres) > 0) {
                foreach ($offres as $offre) {
                    $html .= '<tr>';
                    $html .= '<td>' . $offre->getTitle() . '</td>';
                    $html .= '<td>' . $offre->getLocation() . '</td>';
                    $html .= '<td>';
                    $html .= '<a href="' . $this->generateUrl('back.candidat.offres_emploi.show', ['id' => $offre->getId()]) . '" class="btn btn-info btn-sm">Voir détail</a> ';
                    $html .= '<a href="' . $this->generateUrl('app_candidat_candidature_new', ['id' => $offre->getId()]) . '" class="btn btn-success btn-sm">Postuler</a>';
                    $html .= '</td>';
                    $html .= '</tr>';
                }
            } else {
                $html = '<tr><td colspan="3" class="text-center">Aucune offre ne correspond à votre recherche</td></tr>';
            }

            $this->logger->info('=== FIN DE LA RECHERCHE AVANCÉE ===');

            // Renvoyer le HTML généré
            return new Response($html);
        } catch (\Exception $e) {
            $this->logger->error('ERREUR lors de la recherche: ' . $e->getMessage());
            return new Response('<tr><td colspan="3" class="text-center text-danger">Erreur: ' . $e->getMessage() . '</td></tr>', Response::HTTP_INTERNAL_SERVER_ERROR);
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
