<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FormationController extends AbstractController
{
    #[Route('/back-office/formations', name: 'back.formations.index')]
    public function index(): Response
    {
        return $this->render('back_office/formations/index.html.twig', [
            'controller_name' => 'FormationController',
        ]);
    }

    #[Route('/back-office/formations/add', name: 'back.formations.add')]
    public function add(): Response
    {
        return $this->render('back_office/formations/add.html.twig');
    }
}
