<?php
namespace App\Controller;

use App\Entity\Quiz;
use App\Form\QuizType;
use App\Repository\FormationRepository;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{fid}/formations-quiz')]
final class QuizController extends AbstractController
{
    #[Route(name: 'app_quiz_index', methods: ['GET'])]
    public function index(int $fid, Request $request, FormationRepository $formationRepository, QuizRepository $quizRepository): Response
    {
        $formation = $formationRepository->find($fid);

        if (! $formation) {
            throw $this->createNotFoundException('Formation not found');
        }

        $searchQuery = $request->query->get('q');
        if ($searchQuery) {
            $quizzes = $quizRepository->searchQuestion($fid, $searchQuery);
        } else {
            $quizzes = $quizRepository->findBy(['formation' => $fid]);
        }

        return $this->render('quiz/index.html.twig', [
            'formation' => $formation,
            'quizzes'   => $quizzes,
        ]);
    }

    #[Route('/new', name: 'app_quiz_new', methods: ['GET', 'POST'])]
    public function new (int $fid, Request $request, EntityManagerInterface $entityManager, FormationRepository $formationRepository): Response
    {
        $quiz = new Quiz();
        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formation = $formationRepository->find($fid);
            if (! $formation) {
                throw $this->createNotFoundException('Formation not found');
            }
            $quiz->setFormation($formation);
            $entityManager->persist($quiz);
            $entityManager->flush();
            $this->addFlash('success', 'Question created successfully');

            return $this->redirectToRoute('app_quiz_index', [
                'fid' => $fid,
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('quiz/new.html.twig', [
            'quiz' => $quiz,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_quiz_show', methods: ['GET'])]
    public function show(Quiz $quiz): Response
    {
        return $this->render('quiz/show.html.twig', [
            'quiz' => $quiz,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_quiz_edit', methods: ['GET', 'POST'])]
    public function edit(int $fid, Request $request, Quiz $quiz, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Question updated successfully');

            return $this->redirectToRoute('app_quiz_index', ['fid' => $fid], Response::HTTP_SEE_OTHER);
        }

        return $this->render('quiz/edit.html.twig', [
            'quiz' => $quiz,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_quiz_delete', methods: ['POST'])]
    public function delete(int $fid, Request $request, Quiz $quiz, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $quiz->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($quiz);
            $entityManager->flush();
            $this->addFlash('success', 'Qustion deleted successfully');
        }

        return $this->redirectToRoute('app_quiz_index', ['fid' => $fid], Response::HTTP_SEE_OTHER);
    }
}
