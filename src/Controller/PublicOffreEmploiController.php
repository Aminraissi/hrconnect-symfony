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
        // Note: isActive, typeContrat et localisation ne sont plus des champs mappés dans la base de données
        // Nous récupérons donc toutes les offres sans filtrage
        $offres = $offreEmploiRepository->findBy([], ['id' => 'DESC']);

        return $this->render('public/offre_emploi/index_back_office.html.twig', [
            'offres' => $offres,
        ]);
    }

    #[Route('/offres-emploi/{id}', name: 'app_public_offre_emploi_show')]
    public function show(OffreEmploi $offre): Response
    {
        // Note: isActive n'est plus un champ mappé dans la base de données
        // Nous ne vérifions donc plus si l'offre est active

        return $this->render('public/offre_emploi/show_back_office.html.twig', [
            'offre' => $offre,
        ]);
    }
}