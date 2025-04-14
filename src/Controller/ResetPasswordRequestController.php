<?php

namespace App\Controller;

use App\Entity\ResetPasswordRequest;
use App\Form\ResetPasswordRequestType;
use App\Repository\ResetPasswordRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reset/password/request')]
final class ResetPasswordRequestController extends AbstractController
{
    #[Route(name: 'app_reset_password_request_index', methods: ['GET'])]
    public function index(ResetPasswordRequestRepository $resetPasswordRequestRepository): Response
    {
        return $this->render('reset_password_request/index.html.twig', [
            'reset_password_requests' => $resetPasswordRequestRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_reset_password_request_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $resetPasswordRequest = new ResetPasswordRequest();
        $form = $this->createForm(ResetPasswordRequestType::class, $resetPasswordRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($resetPasswordRequest);
            $entityManager->flush();

            return $this->redirectToRoute('app_reset_password_request_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reset_password_request/new.html.twig', [
            'reset_password_request' => $resetPasswordRequest,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reset_password_request_show', methods: ['GET'])]
    public function show(ResetPasswordRequest $resetPasswordRequest): Response
    {
        return $this->render('reset_password_request/show.html.twig', [
            'reset_password_request' => $resetPasswordRequest,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reset_password_request_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ResetPasswordRequest $resetPasswordRequest, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ResetPasswordRequestType::class, $resetPasswordRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reset_password_request_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reset_password_request/edit.html.twig', [
            'reset_password_request' => $resetPasswordRequest,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reset_password_request_delete', methods: ['POST'])]
    public function delete(Request $request, ResetPasswordRequest $resetPasswordRequest, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$resetPasswordRequest->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($resetPasswordRequest);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reset_password_request_index', [], Response::HTTP_SEE_OTHER);
    }
}
