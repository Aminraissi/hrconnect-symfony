<?php
namespace App\Controller;

use App\Repository\FormationRepository;
use App\Service\TwilioService;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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

        $link = " https://maps.google.com/?q=" . $formation->getLat() . "," . $formation->getLng();

        $qrCode = new QrCode(
            data: $link,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );

        $writer = new PngWriter();

        $result = $writer->write($qrCode);

        $qr = $result->getDataUri();

        return $this->render('formations/details_formation.twig', [
            'formation' => $formation,
            'qr'        => $qr,
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
    public function shareLocalisation(FormationRepository $formationRepository, Request $request, TwilioService $twilioService, HttpClientInterface $client, $id): Response
    {
        $formation = $formationRepository->find($id);

        if (! $formation) {
            throw $this->createNotFoundException('Formation not found');
        }

        $message = "Bonjour , voila la localisation de la formation : " . $formation->getTitle() . " https://maps.google.com/?q=" . $formation->getLat() . "," . $formation->getLng() . " . Merci de votre participation !";

        if ($request->isMethod('POST')) {

            $recaptchaResponse = $request->request->get('g-recaptcha-response');

            if (! $recaptchaResponse) {
                $this->addFlash('error', 'Veuillez valider le captcha.');
            } else {
                $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                    'body' => [
                        'secret'   => $_ENV['GOOGLE_RECAPTCHA_SECRET_KEY'],
                        'response' => $recaptchaResponse,
                        'remoteip' => $request->getClientIp(),
                    ],
                ]);
                $data = $response->toArray();
                if (! isset($data['success']) || ! $data['success']) {
                    $this->addFlash('error', 'Captcha validation failed. Please try again.');
                } else {
                    $phone = $request->request->get('phone');
                    if (empty($phone) || strlen($phone) !== 8) {
                        $this->addFlash('error', 'Veuillez entrer un numéro de téléphone valide.');
                    } else {
                        $phone = $request->request->get('phone');

                        $twilioService->sendSms("+216" . $phone, $message);

                        $this->addFlash('success', 'La localisation a été partagée avec succès.');
                    }
                }
            }

        }

        return $this->render('formations/formation_share_localisation.html.twig', [
            'formation' => $formation,
            'message'   => $message,
        ]);
    }

    #[Route('/frontoffice/mes-formations', name: 'app_mes_formations')]
    public function mesFormations(FormationRepository $formationRepository, ): Response
    {

        $user = $this->getUser();

        $formations = $formationRepository->findFormationsByUserId($user->getId());

        return $this->render('formations/mes_formations.html.twig', [
            'formations' => $formations,
        ]);
    }
}
