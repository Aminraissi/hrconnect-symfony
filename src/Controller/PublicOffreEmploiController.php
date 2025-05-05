<?php

namespace App\Controller;

use App\Entity\OffreEmploi;
use App\Repository\OffreEmploiRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublicOffreEmploiController extends AbstractController
{
    #[Route('/offres-emploi', name: 'app_public_offres_emploi')]
    public function index(Request $request, OffreEmploiRepository $offreEmploiRepository): Response
    {
        $offres = $offreEmploiRepository->findBy([], ['id' => 'DESC']);

        return $this->render('public/offre_emploi/index_back_office.html.twig', [
            'offres' => $offres,
        ]);
    }

    #[Route('/offres-emploi/{id}', name: 'app_public_offre_emploi_show')]
    public function show(OffreEmploi $offre): Response
    {
        return $this->render('public/offre_emploi/show_back_office.html.twig', [
            'offre' => $offre,
        ]);
    }
}