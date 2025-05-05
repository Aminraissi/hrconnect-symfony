<?php

namespace App\Controller;

use App\Entity\Seminaire;
use App\Form\SeminaireType;
use App\Repository\ParticipationSeminaireRepository;
use App\Repository\SeminaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/seminaire')]
final class SeminaireController extends AbstractController
{
    #[Route('/search', name: 'app_seminaire_search', methods: ['GET'])]
    public function search(Request $request, SeminaireRepository $seminaireRepository): Response
    {
        $query = $request->query->get('q', '');
        $sortBy = $request->query->get('sortBy', 'dateDebut');
        $sortDirection = $request->query->get('sortDirection', 'ASC');

        // Validate sortBy to prevent SQL injection
        $allowedSortFields = ['dateDebut', 'cout', 'nomSeminaire', 'formateur'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'dateDebut';
        }

        // Validate sortDirection
        if (!in_array($sortDirection, ['ASC', 'DESC'])) {
            $sortDirection = 'ASC';
        }

        $seminaires = $seminaireRepository->findByNomSeminaireOrFormateur($query, $sortBy, $sortDirection);

        return $this->render('seminaire/_search_results.html.twig', [
            'seminaires' => $seminaires,
        ]);
    }

    #[Route(name: 'app_seminaire_index', methods: ['GET'])]
    public function index(SeminaireRepository $seminaireRepository): Response
    {
        return $this->render('seminaire/index.html.twig', [
            'seminaires' => $seminaireRepository->findAll(),
        ]);
    }

    #[Route('/back', name: 'app_seminaire_index_back', methods: ['GET'])]
    public function index_back(SeminaireRepository $seminaireRepository): Response
    {
        return $this->render('seminaire/index_back.html.twig', [
            'seminaires' => $seminaireRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_seminaire_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $seminaire = new Seminaire();
        $form = $this->createForm(SeminaireType::class, $seminaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($seminaire);
            $entityManager->flush();

            // Send email notification
            $email = (new Email())
                ->from('medlhr0@gmail.com')
                ->to('medlhr0@gmail.com')
                ->subject('Nouveau Séminaire Créé: ' . $seminaire->getNomSeminaire())
                ->text($this->getSeminaireDetails($seminaire, 'créé'));

            $mailer->send($email);

            return $this->redirectToRoute('app_seminaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('seminaire/new.html.twig', [
            'seminaire' => $seminaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_seminaire_show', methods: ['GET'])]
    public function show(Seminaire $seminaire): Response
    {
        return $this->render('seminaire/show.html.twig', [
            'seminaire' => $seminaire,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_seminaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Seminaire $seminaire, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $form = $this->createForm(SeminaireType::class, $seminaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Send email notification
            $email = (new Email())
                ->from('medlhr0@gmail.com')
                ->to('medlhr0@gmail.com')
                ->subject('Séminaire Modifié: ' . $seminaire->getNomSeminaire())
                ->text($this->getSeminaireDetails($seminaire, 'modifié'));

            $mailer->send($email);

            return $this->redirectToRoute('app_seminaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('seminaire/edit.html.twig', [
            'seminaire' => $seminaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_seminaire_delete', methods: ['POST'])]
    public function delete(Request $request, Seminaire $seminaire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$seminaire->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($seminaire);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_seminaire_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/stats/aaa', name: 'app_seminaire_stats', methods: ['GET'])]
    public function stats(ParticipationSeminaireRepository $participationRepository): Response
    {
        $statusStats = $participationRepository->getStatusDistribution();
        $costRanges = $participationRepository->getCostRangeStats();
        $inscriptionTrend = $participationRepository->getInscriptionTrend();

        return $this->render('seminaire/stats.html.twig', [
            'statusStats' => $statusStats,
            'costRanges' => $costRanges,
            'inscriptionTrend' => $inscriptionTrend,
        ]);
    }

    #[Route('/{id}/participants', name: 'app_seminaire_participants', methods: ['GET'])]
    public function participants(Seminaire $seminaire, ParticipationSeminaireRepository $participationRepository): Response
    {
        $participants = $participationRepository->findBy(['seminaire' => $seminaire]);

        return $this->render('seminaire/participants.html.twig', [
            'seminaire' => $seminaire,
            'participants' => $participants,
        ]);
    }

    /**
     * Generates a text string with all seminar details for email content.
     */
    private function getSeminaireDetails(Seminaire $seminaire, string $action): string
    {
        return sprintf(
            "Un séminaire a été %s dans HRConnect.\n\n" .
            "Détails du séminaire:\n" .
            "ID: %s\n" .
            "Nom: %s\n" .
            "Description: %s\n" .
            "Date de début: %s\n" .
            "Date de fin: %s\n" .
            "Lieu: %s\n" .
            "Formateur: %s\n" .
            "Coût: %s DT\n" .
            "Type: %s\n\n" .
            "Ce courriel est généré automatiquement par HRConnect.",
            $action,
            $seminaire->getId(),
            $seminaire->getNomSeminaire(),
            $seminaire->getDescription() ?? 'Aucune',
            $seminaire->getDateDebut()->format('d/m/Y'),
            $seminaire->getDateFin()->format('d/m/Y'),
            $seminaire->getLieu() ?? 'Non spécifié',
            $seminaire->getFormateur(),
            $seminaire->getCout() ?? 'Non spécifié',
            $seminaire->getType() ?? 'Non spécifié'
        );
    }
}