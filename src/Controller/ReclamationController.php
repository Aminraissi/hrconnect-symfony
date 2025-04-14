<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\TicketReclamation;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use App\Repository\TicketReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reclamation')]
class ReclamationController extends AbstractController
{
    #[Route('', name: 'app_reclamation_index', methods: ['GET'])]
    public function index(ReclamationRepository $reclamationRepository): Response
    {
        // Sorting by id DESC instead of non-existent 'dateOfSubmission'
        $reclamations = $reclamationRepository->findBy([], ['id' => 'DESC']);

        return $this->render('reclamation/index.html.twig', [
            'reclamations' => $reclamations,
        ]);
    }

    #[Route('/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($reclamation);
            $em->flush();

            $this->addFlash('success', 'Reclamation submitted successfully.');
            return $this->redirectToRoute('app_reclamation_index');
        }

        return $this->render('reclamation/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reclamation_show', methods: ['GET'])]
    public function show(Reclamation $reclamation, TicketReclamationRepository $ticketRepo): Response
    {
        $ticket = $ticketRepo->findOneBy(['reclamation' => $reclamation]);

        return $this->render('reclamation/show.html.twig', [
            'reclamation' => $reclamation,
            'ticket' => $ticket,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reclamation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Reclamation updated.');
            return $this->redirectToRoute('app_reclamation_index');
        }

        return $this->render('reclamation/edit.html.twig', [
            'form' => $form,
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/{id}', name: 'app_reclamation_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $reclamation->getId(), $request->request->get('_token'))) {
            $em->remove($reclamation);
            $em->flush();

            $this->addFlash('success', 'Reclamation deleted.');
        }

        return $this->redirectToRoute('app_reclamation_index');
    }
}
