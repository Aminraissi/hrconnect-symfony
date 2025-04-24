<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Form\CandidatureSuiviType;
use App\Repository\CandidatureRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/candidature-suivi')]
class CandidatureSuiviController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/', name: 'app_candidature_suivi')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(CandidatureSuiviType::class);
        $form->handleRequest($request);

        return $this->render('candidature_suivi/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/recherche', name: 'app_candidature_suivi_recherche', methods: ['POST'])]
    public function recherche(Request $request, CandidatureRepository $candidatureRepository): Response
    {
        $form = $this->createForm(CandidatureSuiviType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reference = $form->get('reference')->getData();
            $this->logger->info('Recherche de candidature avec référence: ' . $reference);

            $candidature = $candidatureRepository->findOneBy(['reference' => $reference]);

            if ($candidature) {
                $this->logger->info('Candidature trouvée: ID=' . $candidature->getId());
                return $this->render('candidature_suivi/resultat.html.twig', [
                    'candidature' => $candidature,
                    'found' => true
                ]);
            } else {
                $this->logger->warning('Aucune candidature trouvée avec la référence: ' . $reference);
                $this->addFlash('warning', 'Aucune candidature n\'a été trouvée avec cette référence.');
                return $this->render('candidature_suivi/resultat.html.twig', [
                    'reference' => $reference,
                    'found' => false
                ]);
            }
        }

        return $this->redirectToRoute('app_candidature_suivi');
    }
}
