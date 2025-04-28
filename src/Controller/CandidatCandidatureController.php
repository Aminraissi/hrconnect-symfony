<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\OffreEmploi;
use App\Form\CandidatureSimpleType;
use App\Form\CandidatureSimpleNewType;
use App\Entity\Candidat;
use App\Service\GeminiCvEvaluatorService;
use App\Service\CandidatureEmailService;
use App\Repository\CandidatureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/back-office/candidat/candidatures')]
class CandidatCandidatureController extends AbstractController
{
    private LoggerInterface $logger;
    private EntityManagerInterface $em;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $em)
    {
        $this->logger = $logger;
        $this->em = $em;
    }

    #[Route('/{id}/postuler', name: 'app_candidat_candidature_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        OffreEmploi $offre,
        SluggerInterface $slugger,
        GeminiCvEvaluatorService $geminiCvEvaluator,
        CandidatureEmailService $candidatureEmailService
    ): Response {
        $candidature = new Candidature();

        // Définir l'offre d'emploi avant la validation du formulaire
        $candidature->setOffreEmploi($offre);
        $candidature->setStatus('En cours');

        $this->logger->info('Offre d\'emploi définie : ' . $offre->getId() . ' - ' . $offre->getTitle());

        // Utilisation du nouveau formulaire simplifié
        $form = $this->createForm(CandidatureSimpleNewType::class, $candidature);
        $form->handleRequest($request);

        $this->logger->info('Formulaire soumis: ' . ($form->isSubmitted() ? 'Oui' : 'Non'));
        if ($form->isSubmitted()) {
            $this->logger->info('Formulaire valide: ' . ($form->isValid() ? 'Oui' : 'Non'));
            if (!$form->isValid()) {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->logger->error('Erreurs de validation: ' . implode(', ', $errors));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Création du candidat
            $candidat = new Candidat();
            $candidat->setLastName($form->get('candidat_nom')->getData());
            $candidat->setFirstName($form->get('candidat_prenom')->getData());
            $candidat->setEmail($form->get('candidat_email')->getData());
            $candidat->setPhone($form->get('candidat_telephone')->getData());

            // Vérifier si un candidat avec le même email ou téléphone existe déjà
            $existingCandidat = $this->em->getRepository(Candidat::class)->findOneBy(['email' => $candidat->getEmail()]);
            if (!$existingCandidat) {
                $existingCandidat = $this->em->getRepository(Candidat::class)->findOneBy(['phone' => $candidat->getPhone()]);
            }

            if ($existingCandidat) {
                // Utiliser le candidat existant
                $candidat = $existingCandidat;
                $this->logger->info('Utilisation d\'un candidat existant : ' . $candidat->getFirstName() . ' ' . $candidat->getLastName());
            } else {
                // Persister le nouveau candidat
                $this->em->persist($candidat);
                $this->em->flush(); // Flush pour obtenir l'ID du candidat
                $this->logger->info('Nouveau candidat créé : ' . $candidat->getFirstName() . ' ' . $candidat->getLastName());
            }

            // Associer le candidat à la candidature
            $candidature->setCandidat($candidat);

            // Traitement du CV
            $cvFile = $form->get('cv')->getData();
            if (!$cvFile) {
                $this->addFlash('error', 'Le CV est obligatoire pour postuler à cette offre.');
                return $this->render('candidat/candidature/new_simple.html.twig', [
                    'form' => $form->createView(),
                    'offre' => $offre,
                ]);
            }

            $newFilename = $this->uploadFile($cvFile, 'cv_directory', $slugger);
            if ($newFilename) {
                $candidature->setCv($newFilename);

                // Analyse du CV avec Gemini
                $this->logger->info('Analyse du CV avec Gemini');
                $cvPath = $this->getParameter('cv_directory') . '/' . $newFilename;

                // Générer un ID temporaire pour la candidature
                $tempCandidatureId = uniqid('candidature_');
                $analysisResult = $geminiCvEvaluator->evaluateCv($cvPath, $tempCandidatureId);

                if (!$analysisResult['success']) {
                    $this->logger->error('Erreur lors de l\'analyse du CV: ' . ($analysisResult['message'] ?? 'Erreur inconnue'));
                    // En cas d'erreur d'analyse, mettre la candidature en cours (pour examen manuel)
                    $this->logger->info('Erreur d\'analyse, candidature mise en cours pour examen manuel');
                    $candidature->setStatus(Candidature::STATUS_EN_COURS);
                    $this->addFlash('warning', 'Votre candidature a été soumise, mais une erreur est survenue lors de l\'analyse de votre CV. Votre candidature sera examinée manuellement par notre équipe.');
                } else {
                    $score = $analysisResult['score'];
                    $passed = $analysisResult['passed'];
                    $this->logger->info('Score du CV: ' . $score . '% - Passé: ' . ($passed ? 'Oui' : 'Non'));

                    // Si le CV ne répond pas aux critères minimums, rejeter automatiquement la candidature
                    if (!$passed) {
                        $this->logger->info('CV non conforme aux critères minimums, candidature rejetée automatiquement');
                        $candidature->setStatus(Candidature::STATUS_REFUSEE);
                        $this->addFlash('warning', 'Votre candidature a été soumise, mais votre CV ne répond pas à certains critères importants. Nous vous invitons à consulter nos conseils pour améliorer votre CV.');
                    }
                }
            } else {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'upload du CV.');
                return $this->render('candidat/candidature/new_simple.html.twig', [
                    'form' => $form->createView(),
                    'offre' => $offre,
                ]);
            }

            
            $this->em->persist($candidature);
            $this->em->flush();

            $this->logger->info('Candidature créée avec succès pour l\'offre : ' . $offre->getTitle());

            // Déterminer si la candidature est acceptée (score CV >= 50%)
            $isAccepted = null; // Par défaut, on considère qu'il y a une erreur d'analyse

            if (isset($analysisResult)) {
                if ($analysisResult['success']) {
                    // Analyse réussie, on détermine si le CV est accepté ou non
                    $isAccepted = $analysisResult['passed'];
                }
                // Sinon, on laisse isAccepted à null pour indiquer une erreur d'analyse
            }

            // Envoyer l'email avec la référence de candidature
            $emailSent = $candidatureEmailService->sendConfirmationEmail($candidature, $isAccepted);
            if ($emailSent) {
                $this->logger->info('Email de confirmation envoyé avec succès à : ' . $candidature->getCandidat()->getEmail());
            } else {
                $this->logger->warning('Impossible d\'envoyer l\'email de confirmation à : ' . $candidature->getCandidat()->getEmail());
            }

            // Préparer le message de confirmation
            $successMessage = '<strong>Candidature envoyée !</strong> Votre candidature pour l\'offre "' . $offre->getTitle() . '" a été envoyée avec succès. <br>Un email contenant votre référence de candidature vous a été envoyé.';

            // Si une analyse de CV a été effectuée avec succès, rediriger vers la page d'analyse
            if (isset($tempCandidatureId) && isset($analysisResult) && $analysisResult['success']) {
                $this->addFlash('success', $successMessage);
                return $this->redirectToRoute('app_candidat_candidature_cv_analysis', [
                    'id' => $tempCandidatureId
                ]);
            }

            // Sinon, rediriger vers la liste des offres d'emploi
            $this->addFlash('success', $successMessage);
            return $this->redirectToRoute('back.candidat.offres_emploi.index');
        }

        return $this->render('candidat/candidature/new_simple.html.twig', [
            'form' => $form->createView(),
            'offre' => $offre,
        ]);
    }

    /**
     * Affiche les résultats de l'analyse du CV
     */
    #[Route('/cv-analysis/{id}', name: 'app_candidat_candidature_cv_analysis')]
    public function showCvAnalysis(string $id, GeminiCvEvaluatorService $geminiCvEvaluator): Response
    {
        // Récupérer les résultats d'analyse depuis la session
        $analysisResults = $geminiCvEvaluator->getAnalysisResults($id);

        if (!$analysisResults) {
            $this->addFlash('error', 'Les résultats d\'analyse du CV ne sont pas disponibles.');
            return $this->redirectToRoute('back.candidat.offres_emploi.index');
        }

        return $this->render('candidat/candidature/cv_analysis.html.twig', [
            'analysis' => $analysisResults
        ]);
    }

    /**
     * Méthode utilitaire pour uploader un fichier
     */
    private function uploadFile($file, $directoryParam, SluggerInterface $slugger): ?string
    {
        try {
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

            $directory = $this->getParameter($directoryParam);
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }

            $file->move($directory, $newFilename);
            $this->logger->info('Fichier uploadé : ' . $newFilename);
            return $newFilename;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'upload du fichier: ' . $e->getMessage());
            return null;
        }
    }
}
