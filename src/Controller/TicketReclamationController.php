<?php

namespace App\Controller;

use App\Entity\TicketReclamation;
use App\Form\TicketReclamationType;
use App\Repository\TicketReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ticket/reclamation')]
class TicketReclamationController extends AbstractController
{
    #[Route('', name: 'app_ticket_reclamation_index', methods: ['GET'])]
    public function index(TicketReclamationRepository $ticketRepo): Response
    {
        $tickets = $ticketRepo->findBy([], ['id' => 'DESC']);

        return $this->render('ticket_reclamation/index.html.twig', [
            'ticket_reclamations' => $tickets,
        ]);
    }

    #[Route('/new', name: 'app_ticket_reclamation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $ticket = new TicketReclamation();
        $form = $this->createForm(TicketReclamationType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Set the current date/time if not already provided in the form
                if (!$ticket->getDateOfResponse()) {
                    $ticket->setDateOfResponse(new \DateTime());
                }

                $em->persist($ticket);
                $em->flush();

                $this->addFlash('success', 'Réponse enregistrée pour la réclamation.');
                return $this->redirectToRoute('app_ticket_reclamation_index');
            } else {
                $this->addFlash('error', 'Le formulaire contient des erreurs. Veuillez les corriger.');
            }
        }

        return $this->render('ticket_reclamation/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_ticket_reclamation_show', methods: ['GET'])]
public function show(TicketReclamation $ticket): Response
{
    if (!$ticket) {
        throw $this->createNotFoundException('Ticket not found');
    }

    return $this->render('ticket_reclamation/show.html.twig', [
        'ticket' => $ticket,
    ]);
}


    #[Route('/{id}/edit', name: 'app_ticket_reclamation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TicketReclamation $ticket, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TicketReclamationType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Ticket updated.');
            return $this->redirectToRoute('app_ticket_reclamation_index');
        }

        return $this->render('ticket_reclamation/edit.html.twig', [
            'form' => $form->createView(),
            'ticket' => $ticket,
        ]);
    }
}
