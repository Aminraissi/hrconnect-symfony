<?php

namespace App\Controller;

use App\Entity\DemandeConge;
use App\Entity\ValiderConge;
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
use App\Service\WeatherService;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
            'searchTerm' => $search,
        ]);
    }

    #[Route('/new', name: 'app_demande_conge_new', methods: ['GET', 'POST'])]
public function new(
    Request $request,
    EntityManagerInterface $entityManager,
    MailerInterface $mailer,
    HttpClientInterface $httpClient
): Response {
    $demandeConge = new DemandeConge();
    $form = $this->createForm(DemandeCongeType::class, $demandeConge);
    $form->handleRequest($request);

    // Récupération des données météo pour Paris (7 jours)
    $weatherData = [];
    try {
        $response = $httpClient->request(
            'GET',
            'http://api.weatherapi.com/v1/forecast.json',
            [
                'query' => [
                    'key' => 'd2a837751725420a982135319252804',
                    'q' => 'Tunis',
                    'days' => 7,
                    'lang' => 'fr'
                ]
            ]
        );

        if ($response->getStatusCode() === 200) {
            $weatherData = $response->toArray();
        } else {
            $this->addFlash('warning', 'Service météo temporairement indisponible');
        }
    } catch (\Exception $e) {
        $this->addFlash('warning', 'Impossible de charger les données météo: '.$e->getMessage());
    }

    if ($form->isSubmitted() && $form->isValid()) {
        $employe = $demandeConge->getEmploye();
        $dateDebut = $demandeConge->getDateDebut();
        $dateFin = $demandeConge->getDateFin();

        if ($employe && $dateDebut && $dateFin) {
            $daysRequested = $dateDebut->diff($dateFin)->days + 1;
            $newBalance = $employe->getSoldeConges() - $daysRequested;

            if ($newBalance < -7) {
                $this->addFlash('error', 'Le solde des congés ne peut pas descendre en dessous de -7. Demande refusée.');
                return $this->render('demande_conge/new.html.twig', [
                    'demande_conge' => $demandeConge,
                    'form' => $form,
                    'weather' => $weatherData,
                ]);
            }

            $employe->setSoldeConges($newBalance);
            $entityManager->persist($employe);
            $entityManager->persist($demandeConge);

            $validerConge = new ValiderConge();
            $validerConge->setDemandeConge($demandeConge);
            $validerConge->setStatut('EN_ATTENTE');
            $validerConge->setDateValidation(new \DateTime());
            $entityManager->persist($validerConge);

            $entityManager->flush();

            // Envoi d'email
            try {
                $email = (new Email())
                    ->from('chikenbrain26@gmail.com')
                    ->to('chikenbrain26@gmail.com')
                    ->subject('Nouvelle demande de congé - ' . $demandeConge->getTypeConge())
                    ->html($this->renderView(
                        'emails/nouvelle_demande_conge.html.twig',
                        ['demande' => $demandeConge]
                    ));

                $mailer->send($email);
                $this->addFlash('success', 'Email envoyé avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', "Échec de l'envoi de l'email : " . $e->getMessage());
            }

            return $this->redirectToRoute('app_demande_conge_index', [], Response::HTTP_SEE_OTHER);
        }
    }

    return $this->render('demande_conge/new.html.twig', [
        'demande_conge' => $demandeConge,
        'form' => $form,
        'weather' => $weatherData,
    ]);
}

    #[Route('/{id}/edit', name: 'app_demande_conge_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, DemandeConge $demandeConge, EntityManagerInterface $entityManager): Response
    {
        $originalDaysRequested = 0;
        $employe = $demandeConge->getEmploye();
    
        // Calculate the original leave duration to restore balance if needed
        if ($employe && $demandeConge->getDateDebut() && $demandeConge->getDateFin()) {
            $originalDaysRequested = $demandeConge->getDateDebut()->diff($demandeConge->getDateFin())->days + 1;
            $employe->setSoldeConges($employe->getSoldeConges() + $originalDaysRequested);
        }
    
        $form = $this->createForm(DemandeCongeType::class, $demandeConge);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $dateDebut = $demandeConge->getDateDebut();
            $dateFin = $demandeConge->getDateFin();
    
            if ($employe && $dateDebut && $dateFin) {
                $newDaysRequested = $dateDebut->diff($dateFin)->days + 1; // Include both start and end dates
                $newBalance = $employe->getSoldeConges() - $newDaysRequested;
    
                // Prevent editing if the balance will go below -7
                if ($newBalance < -7) {
                    // Restore the original balance
                    $employe->setSoldeConges($employe->getSoldeConges() - $originalDaysRequested + $newDaysRequested);
                    $this->addFlash('error', 'Le solde des congés ne peut pas descendre en dessous de -7. Modification refusée.');
                    return $this->render('demande_conge/edit.html.twig', [
                        'demande_conge' => $demandeConge,
                        'form' => $form,
                    ]);
                }
    
                // Update leave balance
                $employe->setSoldeConges($newBalance);
                $entityManager->persist($employe);
    
                // Persist the updated leave request
                $entityManager->flush();
    
                return $this->redirectToRoute('app_demande_conge_index', [], Response::HTTP_SEE_OTHER);
            }
        }
    
        return $this->render('demande_conge/edit.html.twig', [
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
    #[Route('/{id}', name: 'app_demande_conge_delete', methods: ['POST'])]
    public function delete(Request $request, DemandeConge $demandeConge, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $demandeConge->getId(), $request->get('_token'))) {
            $entityManager->remove($demandeConge);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_demande_conge_index', [], Response::HTTP_SEE_OTHER);
    }

    private function sendConfirmationEmail(DemandeConge $demandeConge, MailerInterface $mailer): void
    {
        try {
            $email = (new Email())
                ->from('chikenbrain26@gmail.com')
                ->to('chikenbrain26@gmail.com')
                ->subject('Nouvelle demande de congé - ' . $demandeConge->getTypeConge())
                ->html($this->renderView(
                    'emails/nouvelle_demande_conge.html.twig',
                    ['demande' => $demandeConge]
                ));

            $mailer->send($email);
            $this->addFlash('success', 'Email envoyé avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', "Échec de l'envoi de l'email : " . $e->getMessage());
        }
    }
}
