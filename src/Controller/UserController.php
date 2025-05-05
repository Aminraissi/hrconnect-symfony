<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Form\UserProfileType;
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
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(
        UserRepository $userRepository,
        Request $request,
        PaginatorInterface $paginator,
        EntityManagerInterface $em
    ): Response {
        // Toggle logic (already present)
        $toggleUserId = $request->query->get('toggleUser');
        if ($toggleUserId) {
            $userToToggle = $userRepository->find($toggleUserId);
            if ($userToToggle) {
                $roles = $userToToggle->getRoles();
                if (in_array('ROLE_INACTIVE', $roles)) {
                    $roles = array_filter($roles, fn($r) => $r !== 'ROLE_INACTIVE');
                } else {
                    $roles[] = 'ROLE_INACTIVE';
                }
                $userToToggle->setRoles(array_values($roles));
                $em->flush();
            }
            return $this->redirectToRoute('app_user_index');
        }
    
        // Search & Sort
        $search = $request->query->get('search');
        $sortField = $request->query->get('sort', 'u.nom');
        $sortDirection = $request->query->get('direction', 'asc');
    
        $qb = $userRepository->createQueryBuilder('u');
    
        if ($search) {
            $qb->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search OR u.cin LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
    
        $qb->orderBy($sortField, $sortDirection);
    
        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            10
        );
    
        return $this->render('user/index.html.twig', [
            'pagination' => $pagination,
            'search' => $search,
            'sort' => $sortField,
            'direction' => $sortDirection,
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
    public function profileFront(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
    
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to access your profile.');
        }
    
        $originalPassword = $user->getPassword(); 
    
        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
    
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            } else {
                $user->setPassword($originalPassword);
            }
    
            // Handle Profile Picture Upload
            $profilePictureFile = $form->get('profilePicture')->getData();
    
            if ($profilePictureFile) {
                $originalFilename = pathinfo($profilePictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$profilePictureFile->guessExtension();
    
                try {
                    $profilePictureFile->move(
                        $this->getParameter('profile_pictures_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Handle exception if something happens during file upload
                }
    
                $user->setProfilePicture($newFilename);
            }
    
            $user->setUpdatedAt(new \DateTime());
    
            $entityManager->persist($user);
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
