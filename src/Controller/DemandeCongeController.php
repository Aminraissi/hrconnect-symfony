<?php

namespace App\Controller;

use App\Entity\DemandeConge;
use App\Form\DemandeCongeType;
use App\Repository\DemandeCongeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/demande/conge')]
final class DemandeCongeController extends AbstractController
{
    #[Route(name: 'app_demande_conge_index', methods: ['GET'])]
    public function index(
        Request $request,
        DemandeCongeRepository $demandeCongeRepository,
        PaginatorInterface $paginator
    ): Response {
        $queryBuilder = $demandeCongeRepository->createQueryBuilder('d')
            ->leftJoin('d.employe', 'e')
            ->addSelect('e');

        if ($search = $request->query->get('search')) {
            $queryBuilder
                ->andWhere('e.nom LIKE :search OR e.prenom LIKE :search OR d.typeConge LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $sortField = $request->query->get('sort', 'd.dateDebut');
        $sortDirection = $request->query->get('direction', 'DESC');
        $queryBuilder->orderBy($sortField, $sortDirection);

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('demande_conge/index.html.twig', [
            'pagination' => $pagination,
            'sortField' => $sortField,
            'direction' => $sortDirection,
        ]);
    }

    #[Route('/new', name: 'app_demande_conge_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $demandeConge = new DemandeConge();
        $form = $this->createForm(DemandeCongeType::class, $demandeConge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($demandeConge);
            $entityManager->flush();

            // Envoi du mail
            $this->sendConfirmationEmail($demandeConge, $mailer);

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
        if ($this->isCsrfTokenValid('delete' . $demandeConge->getId(), $request->request->get('_token'))) {
            if ($demandeConge->getStatut() !== 'EN_ATTENTE') {
                $this->addFlash('error', 'Suppression autorisée uniquement pour les statuts "EN_ATTENTE".');
                return $this->redirectToRoute('app_demande_conge_index');
            }

            $entityManager->remove($demandeConge);
            $entityManager->flush();
            $this->addFlash('success', 'Demande supprimée avec succès.');
        }

        return $this->redirectToRoute('app_demande_conge_index');
    }

    private function sendConfirmationEmail(DemandeConge $demandeConge, MailerInterface $mailer): void
    {
        try {
            $email = (new Email())
                ->from('chikenbrain26@gmail.com')
                ->to('chikenbrain26@gmail.com') // Envoi à la même adresse
                ->subject('Nouvelle demande de congé - ' . $demandeConge->getTypeConge())
                ->html($this->renderView(
                    'emails/nouvelle_demande_conge.html.twig',
                    ['demande' => $demandeConge]
                ));

            $mailer->send($email);
            $this->addFlash('success', 'Email envoyé avec succès !');

        } catch (TransportExceptionInterface $e) {
            $this->addFlash('error', "Échec de l'envoi de l'email : " . $e->getMessage());
        }
    }

}