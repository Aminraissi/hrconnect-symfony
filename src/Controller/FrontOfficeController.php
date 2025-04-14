<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FrontOfficeController extends AbstractController
{
    #[Route('/', name: 'app_front_office')]
    public function index(): Response
    {
        return $this->render('index.html.twig', [
            'controller_name' => 'FrontOfficeController',
        ]);
    }
}
