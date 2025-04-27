<?php

namespace App\Controller;

use App\Entity\ValiderConge;
use App\Form\ValiderCongeType;
use App\Repository\ValiderCongeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/valider/conge')]
final class ValiderCongeController extends AbstractController
{
    #[Route(name: 'app_valider_conge_index', methods: ['GET'])]
    public function index(Request $request, ValiderCongeRepository $validerCongeRepository, PaginatorInterface $paginator): Response
    {
        $query = $validerCongeRepository->createQueryBuilder('v')
            ->leftJoin('v.demandeConge', 'd')
            ->leftJoin('d.employe', 'e')
            ->addSelect('d', 'e');

        $searchTerm = $request->query->get('search', '');
        if ($searchTerm) {
            $query->andWhere('e.nom LIKE :search OR e.prenom LIKE :search OR v.statut LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        $sortField = $request->query->get('sort', 'v.dateValidation');
        $sortDirection = $request->query->get('direction', 'DESC');
        $query->orderBy($sortField, $sortDirection);

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('valider_conge/index.html.twig', [
            'pagination' => $pagination,
            'searchTerm' => $searchTerm,
            'sortField' => $sortField,
            'direction' => $sortDirection,
        ]);
    }

    #[Route('/new', name: 'app_valider_conge_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $validerConge = new ValiderConge();
        $validerConge->setDateValidation(new \DateTime());

        $form = $this->createForm(ValiderCongeType::class, $validerConge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

    #[Route('/{id}/edit', name: 'app_valider_conge_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ValiderConge $validerConge, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ValiderCongeType::class, $validerConge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
        if ($this->isCsrfTokenValid('delete' . $validerConge->getId(), $request->request->get('_token'))) {
            $entityManager->remove($validerConge);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_valider_conge_index', [], Response::HTTP_SEE_OTHER);
    }
}