<?php

namespace App\Controller;

use App\Entity\DemandeConge;
use App\Entity\ValiderConge;
use App\Form\DemandeCongeType;
use App\Repository\DemandeCongeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/demande/conge')]
final class DemandeCongeController extends AbstractController
{
    #[Route(name: 'app_demande_conge_index', methods: ['GET'])]
    public function index(DemandeCongeRepository $demandeCongeRepository): Response
    {
        return $this->render('demande_conge/index.html.twig', [
            'demande_conges' => $demandeCongeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_demande_conge_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $demandeConge = new DemandeConge();
        $form = $this->createForm(DemandeCongeType::class, $demandeConge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($demandeConge);
            $entityManager->flush();

            // Automatically create a ValiderConge entry for the new DemandeConge
            $validerConge = new ValiderConge();
            $validerConge->setDemandeConge($demandeConge);
            $validerConge->setStatut('EN_ATTENTE'); // Default status
            $validerConge->setDateValidation(new \DateTime()); // Set current date as validation date
            $entityManager->persist($validerConge);
            $entityManager->flush();

            return $this->redirectToRoute('app_demande_conge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('demande_conge/new.html.twig', [
            'demande_conge' => $demandeConge,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_demande_conge_show', methods: ['GET'])]
    public function show(DemandeConge $demandeConge): Response
    {
        return $this->render('demande_conge/show.html.twig', [
            'demande_conge' => $demandeConge,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_demande_conge_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, DemandeConge $demandeConge, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DemandeCongeType::class, $demandeConge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_demande_conge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('demande_conge/edit.html.twig', [
            'demande_conge' => $demandeConge,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_demande_conge_delete', methods: ['POST'])]
    public function delete(Request $request, DemandeConge $demandeConge, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$demandeConge->getId(), $request->request->get('_token'))) {
            $entityManager->remove($demandeConge);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_demande_conge_index', [], Response::HTTP_SEE_OTHER);
    }
}