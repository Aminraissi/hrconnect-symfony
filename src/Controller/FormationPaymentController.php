<?php
namespace App\Controller;

use App\Service\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class FormationPaymentController extends AbstractController
{
    #[Route('/formation/payment', name: 'app_formation_payment')]
    public function index(StripeService $stripe): RedirectResponse
    {

        $successUrl = $this->generateUrl('app_formation_payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $cancelUrl  = $this->generateUrl('app_formation_payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $session = $stripe->createCheckoutSession(
            [[
                'price_data' => [
                    'currency'     => 'usd',
                    'product_data' => [
                        'name' => 'Test Product',
                    ],
                    'unit_amount'  => 1000,
                ],
                'quantity'   => 1,
            ]],
            $successUrl,
            $cancelUrl,
        );

        return new RedirectResponse($session->url);
    }

    #[Route('/formation/payment/success', name: 'app_formation_payment_success')]
    public function success(): Response
    {
        return $this->render('payment/success.html.twig', [
            'message' => 'Payment was successful!',
        ]);
    }

    #[Route('/formation/payment/cancel', name: 'app_formation_payment_cancel')]
    public function cancel(): Response
    {
        return $this->render('payment/cancel.html.twig', [
            'message' => 'Payment was cancelled.',
        ]);
    }
}
