<?php
namespace App\Controller;

use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NotificationController extends AbstractController
{
    #[Route('/backoffice/notification', name: 'app_notification')]
    public function index(Request $request, NotificationService $notificationService): Response
    {

        if ($request->isMethod('POST')) {

            $response = $notificationService->send(
                $request->request->get('title'),
                $request->request->get('description')
            );

            if ($response === false) {
                $this->addFlash('error', 'Failed to send notification.');
            } else {
                $this->addFlash('success', 'Notification sent successfully.');
            }
        }

        return $this->render('notification/index.html.twig', [
            'controller_name' => 'NotificationController',
        ]);
    }
}
