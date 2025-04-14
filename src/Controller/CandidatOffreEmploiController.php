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

        // Utiliser les champs disponibles dans la nouvelle structure
        // Note: isActive et typeContrat ne sont plus disponibles dans la nouvelle structure
        // Nous récupérons donc toutes les offres sans filtrage
        $offres = $repository->findAll();

        return $this->render('back_office/candidat/offres_emploi/index.html.twig', [
            'offres' => $offres,
            'type' => $type,
        ]);
    }

    #[Route('/{id}', name: 'back.candidat.offres_emploi.show')]
    public function show(OffreEmploi $offre): Response
    {
        // Note: isActive n'est plus disponible dans la nouvelle structure
        // Nous ne vérifions donc plus si l'offre est active

        return $this->render('back_office/candidat/offres_emploi/show.html.twig', [
            'offre' => $offre,
        ]);
    }
}
