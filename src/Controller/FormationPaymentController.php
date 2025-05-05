<?php
namespace App\Controller;

use App\Repository\FormationRepository;
use App\Service\PaypalService;
use App\Service\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class FormationPaymentController extends AbstractController
{
    #[Route('/frontoffice/formations/{id}/payment/stripe', name: 'app_formation_payment_stripe')]
    public function index(FormationRepository $formationRepository, StripeService $stripe, $id): RedirectResponse
    {

        $formation = $formationRepository->find($id);

        if (! $formation) {
            throw $this->createNotFoundException('Formation not found');
        }

        $successUrl = $this->generateUrl('app_formation_payment_success', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL);
        $cancelUrl  = $this->generateUrl('app_formation_payment_cancel', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL);

        $session = $stripe->createCheckoutSession(
            [[
                'price_data' => [
                    'currency'     => 'usd',
                    'product_data' => [
                        'name' => $formation->getTitle(),
                    ],
                    'unit_amount'  => (int) ($formation->getPrice() * 100 * 0.34),
                ],
                'quantity'   => 1,
            ]],
            $successUrl,
            $cancelUrl,
        );

        return new RedirectResponse($session->url);
    }

    #[Route('/frontoffice/formations/{id}/payment/paypal', name: 'app_formation_payment_paypal')]
    public function checkout(FormationRepository $formationRepository, PaypalService $paypal, $id): RedirectResponse
    {

        $formation = $formationRepository->find($id);

        if (! $formation) {
            throw $this->createNotFoundException('Formation not found');
        }

        $successUrl = $this->generateUrl('app_formation_payment_success', ['id' => $id], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
        $cancelUrl  = $this->generateUrl('app_formation_payment_cancel', ['id' => $id], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

        $redirectUrl = $paypal->createOrder((int) ($formation->getPrice() * 0.34), $successUrl, $cancelUrl);

        return new RedirectResponse($redirectUrl);
    }

    #[Route('/frontoffice/formations/{id}/payment/success', name: 'app_formation_payment_success')]
    public function success(MailerInterface $mailer, FormationRepository $formationRepository, \Doctrine\ORM\EntityManagerInterface $entityManager, $id): Response
    {

        $formation = $formationRepository->find($id);

        if (! $formation) {
            throw $this->createNotFoundException('Formation not found');
        }

        $user = $this->getUser();

        if (! $user) {
            throw $this->createAccessDeniedException('User not authenticated');
        }

        $formation->addUser($user);

        $entityManager->persist($formation);
        $entityManager->flush();

        $htmlContent = $this->renderView('formations/payment_success_email.html.twig', [
            'name'           => $user->getNom() . ' ' . $user->getPrenom(),
            'formation_name' => $formation->getTitle(),
            'formation_link' => 'http://127.0.0.1:8000/frontoffice/mes-formations',
        ]);

        $email = (new Email())
            ->from('your_email@example.com')
            ->to('haithemdridiweb@gmail.com')
            ->subject('Confirmation de votre paiement pour la formation ' . $formation->getTitle())
            ->html($htmlContent);

        $mailer->send($email);

        $this->addFlash('success', 'Le paiement a été effectué avec succès. Vous avez été inscrit avec succès à la formation ' . $formation->getTitle() . '.');

        return $this->redirectToRoute('app_mes_formations');

    }

    #[Route('/frontoffice/formations/{id}/cancel', name: 'app_formation_payment_cancel')]
    public function cancel($id): Response
    {
        $this->addFlash('error', 'Le paiement a échoué.');

        return $this->redirectToRoute('app_user_formation_checkout', [
            'id' => $id,
        ]);
    }
}
