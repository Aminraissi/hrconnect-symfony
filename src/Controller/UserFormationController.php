<?php
namespace App\Controller;

use App\Repository\FormationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserFormationController extends AbstractController
{
    #[Route('/user/formation', name: 'app_user_formation')]
    public function index(FormationRepository $formationRepository): Response
    {
        return $this->render('formations/liste_formation.html.twig', [
            'formations' => $formationRepository->findAll(),
        ]);
    }
}
