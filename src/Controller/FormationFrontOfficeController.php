<?php
namespace App\Controller;

use App\Repository\FormationRepository;
use App\Service\TwilioService;
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

    #[Route('/frontoffice/formations/{id}/share_localisation', name: 'app_user_formation_share_localisation')]
    public function shareLocalisation(FormationRepository $formationRepository, Request $request, TwilioService $twilioService, $id): Response
    {
        $formation = $formationRepository->find($id);

        if (! $formation) {
            throw $this->createNotFoundException('Formation not found');
        }

        $message = "Bonjour , voila la localisation de la formation : " . $formation->getTitle() . " https://maps.google.com/?q=" . $formation->getLat() . "," . $formation->getLng() . " . Merci de votre participation !";

        if ($request->isMethod('POST')) {
            $phone = $request->request->get('phone');
            if (empty($phone) || strlen($phone) !== 8) {
                $this->addFlash('error', 'Veuillez entrer un numéro de téléphone valide.');
            } else {
                $phone = $request->request->get('phone');

                $twilioService->sendSms("+216" . $phone, $message);

                $this->addFlash('success', 'La localisation a été partagée avec succès.');
            }

        }

        return $this->render('formations/formation_share_localisation.html.twig', [
            'formation' => $formation,
            'message'   => $message,
        ]);
    }

}
