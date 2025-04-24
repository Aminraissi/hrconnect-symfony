<?php
namespace App\Controller;

use App\Repository\FormationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FormationFrontOfficeController extends AbstractController
{
    #[Route('/frontoffice/formations', name: 'app_user_formation')]
    public function index(FormationRepository $formationRepository): Response
    {
        return $this->render('formations/liste_formation.html.twig', [
            'formations' => $formationRepository->findAll(),
        ]);
    }

    #[Route('/frontoffice/formations/{id}', name: 'app_user_formation_details')]
    public function details(FormationRepository $formationRepository, $id): Response
    {

        $formation = $formationRepository->find($id);

        if (! $formation) {
            throw $this->createNotFoundException('Formation not found');
        }

        return $this->render('formations/details_formation.twig', [
            'formation' => $formation,
        ]);
    }

    #[Route('/frontoffice/formations/{id}/checkout', name: 'app_user_formation_checkout', methods: ['GET', 'POST'])]
    public function checkout(FormationRepository $formationRepository, $id, Request $request): Response
    {
        $formation = $formationRepository->find($id);

        if (! $formation) {
            throw $this->createNotFoundException('Formation not found');
        }

        if ($request->isMethod('POST')) {
            $paymentMethod = $request->request->get('paymentMethod');
            if ($paymentMethod == "card") {
                return $this->redirectToRoute('app_formation_payment_stripe', ['id' => $id]);
            } else {
                return $this->redirectToRoute('app_formation_payment_paypal', ['id' => $id]);

            }
        }

        return $this->render('formations/formation_checkout.twig', [
            'formation' => $formation,
        ]);
    }

    #[Route('/frontoffice/formations/{id}/meet', name: 'app_user_formation_meet')]
    public function meet(FormationRepository $formationRepository, $id): Response
    {

        $formation = $formationRepository->find($id);

        if (! $formation) {
            throw $this->createNotFoundException('Formation not found');
        }

        return $this->render('formations/formation_meet.html.twig', [
            'formation' => $formation,
        ]);
    }

}
