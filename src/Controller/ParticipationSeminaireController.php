<?php
namespace App\Controller;

use App\Entity\ParticipationSeminaire;
use App\Form\ParticipationSeminaireType;
use App\Repository\ParticipationSeminaireRepository;
use App\Repository\SeminaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participation/seminaire')]
final class ParticipationSeminaireController extends AbstractController
{
    #[Route(name: 'app_participation_seminaire_index', methods: ['GET'])]
    public function index(ParticipationSeminaireRepository $participationSeminaireRepository): Response
    {
        return $this->render('participation_seminaire/index.html.twig', [
            'participation_seminaires' => $participationSeminaireRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_participation_seminaire_new', methods: ['GET', 'POST'])]
    public function new (Request $request, EntityManagerInterface $entityManager, SeminaireRepository $seminaireRepository): Response
    {
        $participationSeminaire = new ParticipationSeminaire();

        // Check for seminaire_id in query parameters
        $seminaireId = $request->query->getInt('seminaire_id');
        $seminaire   = null;
        if ($seminaireId) {
            $seminaire = $seminaireRepository->find($seminaireId);
            if (! $seminaire) {
                throw $this->createNotFoundException('Séminaire non trouvé.');
            }
            $participationSeminaire->setSeminaire($seminaire);
        }

        $form = $this->createForm(ParticipationSeminaireType::class, $participationSeminaire, [
            'disabled_seminaire' => $seminaireId !== 0,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($participationSeminaire);
            $entityManager->flush();

            // 👇 Instead of redirect, render a success page
            return $this->render('participation_seminaire/success.html.twig', [
                'participation_seminaire' => $participationSeminaire,
            ]);
        }

        return $this->render('participation_seminaire/new.html.twig', [
            'participation_seminaire' => $participationSeminaire,
            'form'                    => $form,
            'seminaire'               => $seminaireId ? $seminaire : null,
        ]);
    }

    #[Route('/{id}', name: 'app_participation_seminaire_show', methods: ['GET'])]
    public function show(ParticipationSeminaire $participationSeminaire): Response
    {
        return $this->render('participation_seminaire/show.html.twig', [
            'participation_seminaire' => $participationSeminaire,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_participation_seminaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ParticipationSeminaire $participationSeminaire, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ParticipationSeminaireType::class, $participationSeminaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_participation_seminaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('participation_seminaire/edit.html.twig', [
            'participation_seminaire' => $participationSeminaire,
            'form'                    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_participation_seminaire_delete', methods: ['POST'])]
    public function delete(Request $request, ParticipationSeminaire $participationSeminaire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $participationSeminaire->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($participationSeminaire);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_participation_seminaire_index', [], Response::HTTP_SEE_OTHER);
    }
}
