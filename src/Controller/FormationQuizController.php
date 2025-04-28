<?php
namespace App\Controller;

use App\Entity\QuizReponse;
use App\Repository\FormationRepository;
use App\Repository\QuizReponseRepository;
use App\Repository\QuizRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class FormationQuizController extends AbstractController
{
    #[Route('/frontoffice/mes-formations/{id}/quiz/{q}', name: 'app_formation_quiz')]
    public function index(QuizRepository $quizRepository, QuizReponseRepository $quizReponseRepository, FormationRepository $formationRepository, Request $request, \Doctrine\ORM\EntityManagerInterface $entityManager, $id, $q = 1): Response
    {
        $formation = $formationRepository->find($id);
        if (! $formation) {
            throw $this->createNotFoundException('Formation not found');
        }

        $questions = $quizRepository->findBy(['formation' => $formation], ['id' => 'ASC']);

        if (! $questions) {
            throw $this->createNotFoundException('Quiz not found for this formation');
        }

        $questionsSize = count($questions);

        $existingResponse = $quizReponseRepository->findOneBy([
            'employe' => $this->getUser(),
            'quiz'    => $questions[0],
        ]);

        if ($existingResponse) {
            return $this->redirectToRoute('app_formation_quiz_result', [
                'id' => $id,
            ]);
        }

        if ($request->isMethod('POST')) {
            $reponse = $request->request->get('reponse');
            if ($reponse != null) {

                $quizReponse = new QuizReponse();
                $quizReponse->setQuiz($questions[$q - 1]);
                $quizReponse->setEmploye($this->getUser());
                $quizReponse->setNumReponse($reponse);

                $em = $entityManager;
                $em->persist($quizReponse);
                $em->flush();

                return $this->redirectToRoute('app_formation_quiz', [
                    'id' => $id,
                    'q'  => $q + 1,
                ]);
            } else {
                $this->addFlash('error', 'Choisissez une réponse');
            }
        }

        if ($q > $questionsSize) {

            return $this->redirectToRoute('app_formation_quiz_result', [
                'id' => $id,
            ]);
        }

        return $this->render('formations/quiz/quiz.html.twig', [
            'questions_size' => $questionsSize,
            'question'       => $questions[$q - 1],
        ]);
    }

    #[Route('/frontoffice/mes-formations/{id}/quiz-result', name: 'app_formation_quiz_result')]
    public function quizResult(FormationRepository $formationRepository, QuizRepository $quizRepository, QuizReponseRepository $quizReponseRepository, MailerInterface $mailer, $id): Response
    {

        $formation = $formationRepository->find($id);
        if (! $formation) {
            throw $this->createNotFoundException('Formation not found');
        }

        $questions     = $quizRepository->findBy(['formation' => $formation]);
        $questionsSize = count($questions);

        $score = 0;
        foreach ($questions as $question) {

            // get user reponse

            $userReponse = $quizReponseRepository->findBy(['employe' => $this->getUser(), 'quiz' => $question]);
            if ($userReponse) {
                if ($userReponse[0]->getNumReponse() == $question->getNum_reponse_correct()) {
                    $score++;
                }
            }
        }

        if ($score >= $questionsSize / 2) {
            $this->addFlash('success', 'Vous avez réussi le quiz avec un score de ' . $score . '/' . $questionsSize);

            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true); // Allow external images if needed
            $dompdf = new Dompdf($options);

            $html = $this->renderView('formations/quiz/attestation.html.twig', [
                'user_name'      => $this->getUser()->getNom() . ' ' . $this->getUser()->getPrenom(),
                'formation_name' => $formation->getTitle(),
            ]);

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $pdfOutput   = $dompdf->output();
            $filePath    = '/uploads/pdf/' . $id . '_' . $this->getUser()->getId() . '.pdf';
            $pdfFilepath = $this->getParameter('kernel.project_dir') . '/public' . $filePath;

            // Ensure the directory exists
            $directory = dirname($pdfFilepath);
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($pdfFilepath, $pdfOutput);

            // envoyer l'e pdf par mail

            $email = (new Email())
                ->from('example@example.com')
                ->to($this->getUser()->getEmail())
                ->subject('Attestation de réussite de la formation ' . $formation->getTitle())
                ->html('<p>Bonjour, veuillez trouver ci-joint votre attestation de réussite de la formation <strong>' . $formation->getTitle() . '</strong></p>')
                ->attachFromPath(
                    $this->getParameter('kernel.project_dir') . '/public/' . $filePath,
                    'attestation.pdf',
                    'application/pdf'
                );

            $mailer->send($email);

        } else {
            $this->addFlash('error', 'Vous avez échoué le quiz avec un score de ' . $score . '/' . $questionsSize);
        }

        return $this->render('formations/quiz/result.html.twig', [
            'formation' => $formation,
            'filePath'  => $filePath ?? null,
        ]);
    }

    #[Route('/frontoffice/mes-formations/{id}/quiz/{q}/correction', name: 'app_formation_quiz_correction')]
    public function correctionQuiz(QuizRepository $quizRepository, QuizReponseRepository $quizReponseRepository, FormationRepository $formationRepository, Request $request, \Doctrine\ORM\EntityManagerInterface $entityManager, $id, $q = 1): Response
    {
        $formation = $formationRepository->find($id);
        if (! $formation) {
            throw $this->createNotFoundException('Formation not found');
        }

        $questions = $quizRepository->findBy(['formation' => $formation], ['id' => 'ASC']);

        if (! $questions) {
            throw $this->createNotFoundException('Quiz not found for this formation');
        }

        $questionsSize = count($questions);

        if ($request->isMethod('POST')) {
            // $reponse = $request->request->get('reponse');
            // if ($reponse != null) {

            //     $quizReponse = new QuizReponse();
            //     $quizReponse->setQuiz($questions[$q - 1]);
            //     $quizReponse->setEmploye($this->getUser());
            //     $quizReponse->setNumReponse($reponse);

            //     $em = $entityManager;
            //     $em->persist($quizReponse);
            //     $em->flush();

            return $this->redirectToRoute('app_formation_quiz_correction', [
                'id' => $id,
                'q'  => $q + 1,
            ]);
            // } else {
            //     $this->addFlash('error', 'Choisissez une réponse');
            // }
        }

        $userReponse = $quizReponseRepository->findOneBy([
            'employe' => $this->getUser(),
            'quiz'    => $questions[$q - 1],
        ]);

        if ($q > $questionsSize) {

            return $this->redirectToRoute('app_formation_quiz_correction', [
                'id' => $id,
            ]);
        }

        return $this->render('formations/quiz/correction.html.twig', [
            'formation'      => $formation,
            'questions_size' => $questionsSize,
            'question'       => $questions[$q - 1],
            'userReponse'    => $userReponse,
        ]);
    }
}
