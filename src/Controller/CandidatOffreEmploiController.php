<?php

namespace App\Controller;

use App\Entity\OffreEmploi;
use App\Repository\OffreEmploiRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/back-office/candidat/offres-emploi')]
class CandidatOffreEmploiController extends AbstractController
{
    #[Route('/', name: 'back.candidat.offres_emploi.index')]
    public function index(Request $request, OffreEmploiRepository $repository): Response
    {
        $type = $request->query->get('type');
        
        $criteria = ['isActive' => true];
        if ($type) {
            $criteria['typeContrat'] = $type;
        }
        
        $offres = $repository->findBy($criteria, ['datePublication' => 'DESC']);
        
        return $this->render('back_office/candidat/offres_emploi/index.html.twig', [
            'offres' => $offres,
            'type' => $type,
        ]);
    }

    #[Route('/{id}', name: 'back.candidat.offres_emploi.show')]
    public function show(OffreEmploi $offre): Response
    {
        // Vérifier si l'offre est active
        if (!$offre->isIsActive()) {
            throw $this->createNotFoundException('Cette offre d\'emploi n\'est pas disponible.');
        }
        
        return $this->render('back_office/candidat/offres_emploi/show.html.twig', [
            'offre' => $offre,
        ]);
    }
}
