<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $query = $userRepository->createQueryBuilder('u')->getQuery();
    
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // Page number
            10 
        );
    
        return $this->render('user/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/myprofile', name: 'app_user_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to access your profile.');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            $entityManager->flush();

            $this->addFlash('success', 'Profile updated successfully!');

            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('user/profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/show/{cin<\d+>}', name: 'app_user_show', methods: ['GET'])]
    public function show(UserRepository $userRepository, int $cin): Response
    {
        $user = $userRepository->findOneBy(['cin' => $cin]);

        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/edit/{cin<\d+>}', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, int $cin): Response
    {
        $user = $userRepository->findOneBy(['cin' => $cin]);

        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            $entityManager->flush();

            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/delete/{cin<\d+>}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, int $cin): Response
    {
        $user = $userRepository->findOneBy(['cin' => $cin]);

        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        if ($this->isCsrfTokenValid('delete' . $user->getCin(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/home', name: 'app_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('home.html.twig');
    }

    #[Route('/profile', name: 'app_user_profile_front', methods: ['GET', 'POST'])]
    public function profileFront(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to access your profile.');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            $entityManager->flush();

            $this->addFlash('success', 'Profile updated successfully!');

            return $this->redirectToRoute('app_user_profile_front');
        }

        return $this->render('user/profile_front.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/user/download/pdf', name: 'app_user_download_pdf', methods: ['GET'])]
public function downloadPdf(UserRepository $userRepository): Response
{
    $users = $userRepository->findAll();

    // Generate HTML for PDF
    $html = $this->renderView('user/pdf.html.twig', [
        'users' => $users,
    ]);

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    return new Response($dompdf->stream('users_list.pdf', ["Attachment" => true]));
}
}
