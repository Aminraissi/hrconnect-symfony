<?php

namespace App\Controller;

use App\Entity\ValiderConge;
use App\Form\ValiderCongeType;
use App\Repository\ValiderCongeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface; // Pour la pagination

#[Route('/valider/conge')]
final class ValiderCongeController extends AbstractController
{
    #[Route(name: 'app_valider_conge_index', methods: ['GET'])]
    public function index(Request $request, ValiderCongeRepository $validerCongeRepository, PaginatorInterface $paginator): Response
    {
        // Paramètres de recherche (par exemple, filtre par statut)
        $searchTerm = $request->query->get('search', '');

        // Récupérer les résultats de la recherche et appliquer la pagination
        $queryBuilder = $validerCongeRepository->createQueryBuilder('v')
            ->leftJoin('v.demandeConge', 'd')
            ->leftJoin('d.employe', 'e')
            ->where('e.nom LIKE :search OR e.prenom LIKE :search OR v.statut LIKE :search')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('v.dateValidation', 'DESC'); // Tri par date de validation par défaut

        $pagination = $paginator->paginate(
            $queryBuilder, 
            $request->query->getInt('page', 1), // Page actuelle
            10 // Nombre d'éléments par page
        );

        return $this->render('valider_conge/index.html.twig', [
            'valider_conges' => $pagination,
            'searchTerm' => $searchTerm, // Passer la valeur de recherche à la vue
        ]);
    }


    #[Route('/new', name: 'app_valider_conge_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $validerConge = new ValiderConge();
        $validerConge->setDateValidation(new \DateTime()); // Automatically set today's date

        $form = $this->createForm(ValiderCongeType::class, $validerConge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Synchronize the statut with the related DemandeConge
            $demandeConge = $validerConge->getDemandeConge();
            if ($demandeConge) {
                $demandeConge->setStatut($validerConge->getStatut());
                $entityManager->persist($demandeConge);
            }

            $entityManager->persist($validerConge);
            $entityManager->flush();

            return $this->redirectToRoute('app_valider_conge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('valider_conge/new.html.twig', [
            'valider_conge' => $validerConge,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_valider_conge_show', methods: ['GET'])]
    public function show(ValiderConge $validerConge): Response
    {
        return $this->render('valider_conge/show.html.twig', [
            'valider_conge' => $validerConge,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_valider_conge_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ValiderConge $validerConge, EntityManagerInterface $entityManager): Response
    {
        $validerConge->setDateValidation(new \DateTime()); // Automatically update the date to today's date

        $form = $this->createForm(ValiderCongeType::class, $validerConge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Synchronize the statut with the related DemandeConge
            $demandeConge = $validerConge->getDemandeConge();
            if ($demandeConge) {
                $demandeConge->setStatut($validerConge->getStatut());
                $entityManager->persist($demandeConge);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_valider_conge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('valider_conge/edit.html.twig', [
            'valider_conge' => $validerConge,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_valider_conge_delete', methods: ['POST'])]
    public function delete(Request $request, ValiderConge $validerConge, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$validerConge->getId(), $request->request->get('_token'))) {
            $demandeConge = $validerConge->getDemandeConge();

            // Remove the ValiderConge
            $entityManager->remove($validerConge);
            $entityManager->flush();

            // Check if DemandeConge has no more ValiderConge entries
            if ($demandeConge && $demandeConge->getValiderConges()->isEmpty()) {
                $entityManager->remove($demandeConge);
                $entityManager->flush();
            }
        }

        return $this->redirectToRoute('app_valider_conge_index', [], Response::HTTP_SEE_OTHER);
    }
}