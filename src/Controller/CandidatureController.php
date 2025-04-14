<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\OffreEmploi;
use App\Form\CandidatureType;
use App\Form\CandidaturePublicType;
use App\Form\CandidatureSimpleType;
use App\Repository\CandidatureRepository;
use App\Repository\OffreEmploiRepository;
use App\Service\EmailService;
use App\Service\CvAnalyzerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;

#[Route('/back-office/candidatures')]
class CandidatureController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $em,
        private readonly EmailService $emailService,
        private readonly CvAnalyzerService $cvAnalyzerService
    ) {
    }

    #[Route('/', name: 'back.candidatures.index')]
    public function index(CandidatureRepository $repository, OffreEmploiRepository $offreRepository, Request $request): Response
    {
        $offreId = $request->query->get('offre');
        $candidatures = $offreId
            ? $repository->findBy(['offre' => $offreId], ['dateCandidature' => 'DESC'])
            : $repository->findBy([], ['dateCandidature' => 'DESC']);

        return $this->render('back_office/candidatures/index.html.twig', [
            'candidatures' => $candidatures,
            'offres' => $offreRepository->findAll(),
            'offreId' => $offreId,
        ]);
    }

    #[Route('/{id}/edit', name: 'back.candidatures.edit', methods: ['GET', 'POST'])]
    public function edit(Candidature $candidature, Request $request): Response
    {
        try {
            $form = $this->createForm(CandidatureType::class, $candidature);

            if ($request->isMethod('POST')) {
                $this->logger->info('Requête POST reçue pour modifier une candidature');

                $data = $request->request->all('candidature');

                if (isset($data)) {
                    $candidature->setNom($data['nom'] ?? '');
                    $candidature->setPrenom($data['prenom'] ?? '');
                    $candidature->setEmail($data['email'] ?? '');
                    $candidature->setTelephone($data['telephone'] ?? '');
                    $candidature->setMessage($data['message'] ?? '');
                    $candidature->setStatut($data['statut'] ?? 'en_attente');

                    try {
                        $this->em->flush();

                        $this->logger->info('Candidature modifiée : ' . $candidature->getNom() . ' ' . $candidature->getPrenom());
                        $this->addFlash('success', 'La candidature a été modifiée avec succès');
                        return $this->redirectToRoute('back.candidatures.index');
                    } catch (\Exception $e) {
                        $this->logger->error('Erreur lors de la modification : ' . $e->getMessage());
                        $this->addFlash('error', 'Une erreur est survenue lors de la modification de la candidature');
                    }
                }
            }

            return $this->render('back_office/candidatures/edit.html.twig', [
                'form' => $form->createView(),
                'candidature' => $candidature,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la modification de la candidature : ' . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors de la modification de la candidature.');
            return $this->redirectToRoute('back.candidatures.index');
        }
    }

    #[Route('/{id}/delete', name: 'back.candidatures.delete', methods: ['GET', 'POST'])]
    public function delete(Candidature $candidature): Response
    {
        try {
            $this->em->remove($candidature);
            $this->em->flush();

            $this->logger->info('Candidature supprimée : ' . $candidature->getNom() . ' ' . $candidature->getPrenom());
            $this->addFlash('success', 'La candidature a été supprimée avec succès');
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression de la candidature : ' . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression de la candidature.');
        }

        return $this->redirectToRoute('back.candidatures.index');
    }

    #[Route('/{id}/accept', name: 'back.candidatures.accept', methods: ['GET'])]
    public function accept(Candidature $candidature, CandidatureRepository $candidatureRepository): Response
    {
        try {
            // 1. Récupérer l'offre associée à la candidature
            $offre = $candidature->getOffre();

            // 2. Mettre à jour le statut de la candidature à "acceptee"
            $candidature->setStatut('acceptee');

            // 3. Désactiver l'offre (la clôturer)
            $offre->setIsActive(false);

            // 4. Mettre à jour toutes les autres candidatures pour cette offre à "refusee"
            $autreCandidatures = $candidatureRepository->findBy(['offre' => $offre]);
            foreach ($autreCandidatures as $autreCandidature) {
                if ($autreCandidature->getId() !== $candidature->getId()) {
                    $autreCandidature->setStatut('refusee');
                }
            }

            // 5. Enregistrer les modifications
            $this->em->flush();

            // 6. Envoyer un email d'acceptation au candidat sélectionné
            $this->logger->info('Tentative d\'envoi d\'email d\'acceptation à : ' . $candidature->getEmail());
            $emailSent = $this->emailService->sendEmail($candidature, 'accepted');
            if ($emailSent) {
                $this->logger->info('Email d\'acceptation envoyé avec succès à : ' . $candidature->getEmail());
            } else {
                $this->logger->warning('Impossible d\'envoyer l\'email d\'acceptation à : ' . $candidature->getEmail());
            }

            // 7. Envoyer des emails de refus aux autres candidats
            foreach ($autreCandidatures as $autreCandidature) {
                if ($autreCandidature->getId() !== $candidature->getId()) {
                    $this->logger->info('Tentative d\'envoi d\'email de refus à : ' . $autreCandidature->getEmail());
                    $emailSent = $this->emailService->sendEmail($autreCandidature, 'rejected');
                    if ($emailSent) {
                        $this->logger->info('Email de refus envoyé avec succès à : ' . $autreCandidature->getEmail());
                    } else {
                        $this->logger->warning('Impossible d\'envoyer l\'email de refus à : ' . $autreCandidature->getEmail());
                    }
                }
            }

            $this->logger->info('Candidature acceptée : ' . $candidature->getNom() . ' ' . $candidature->getPrenom() . ' pour l\'offre : ' . $offre->getTitre());
            $this->addFlash('success', '<strong>Candidature acceptée !</strong> La candidature de ' . $candidature->getNom() . ' ' . $candidature->getPrenom() . ' a été acceptée avec succès. <br>L\'offre "' . $offre->getTitre() . '" a été clôturée et les autres candidatures ont été refusées. <br>Des emails de notification ont été envoyés aux candidats.');
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'acceptation de la candidature : ' . $e->getMessage());
            $this->addFlash('danger', '<strong>Erreur !</strong> Une erreur est survenue lors de l\'acceptation de la candidature. <br>Détail : ' . $e->getMessage());
        }

        return $this->redirectToRoute('back.candidatures.index');
    }

    #[Route('/{id}/view-cv', name: 'back.candidatures.view_cv', methods: ['GET'])]
    public function viewCv(Candidature $candidature): Response
    {
        // Vérifier si le candidat a fourni un CV
        if (!$candidature->getCv()) {
            $this->addFlash('warning', 'Ce candidat n\'a pas fourni de CV.');
            return $this->redirectToRoute('back.candidatures.index');
        }

        // Vérifier si le fichier existe
        $cvPath = $this->getParameter('cv_directory') . '/' . $candidature->getCv();
        if (!file_exists($cvPath)) {
            $this->addFlash('danger', 'Le fichier CV n\'existe pas sur le serveur.');
            return $this->redirectToRoute('back.candidatures.index');
        }

        return $this->render('back_office/candidatures/view_cv.html.twig', [
            'candidature' => $candidature,
            'analysis' => null // Pas d'analyse par défaut
        ]);
    }

    #[Route('/{id}/analyze-cv', name: 'back.candidatures.analyze_cv', methods: ['GET'])]
    public function analyzeCv(Candidature $candidature): Response
    {
        // Vérifier si le candidat a fourni un CV
        if (!$candidature->getCv()) {
            $this->addFlash('warning', 'Ce candidat n\'a pas fourni de CV.');
            return $this->redirectToRoute('back.candidatures.index');
        }

        // Vérifier si le fichier existe
        $cvPath = $this->getParameter('cv_directory') . '/' . $candidature->getCv();
        if (!file_exists($cvPath)) {
            $this->addFlash('danger', 'Le fichier CV n\'existe pas sur le serveur.');
            return $this->redirectToRoute('back.candidatures.index');
        }

        try {
            // Analyser le CV
            $this->logger->info('Début de l\'analyse du CV pour la candidature : ' . $candidature->getId());
            $result = $this->cvAnalyzerService->analyzeCv($cvPath);

            if ($result['success']) {
                $this->logger->info('Analyse du CV réussie pour la candidature : ' . $candidature->getId());
                $analysis = $result['data'];
                $this->addFlash('success', 'Le CV a été analysé avec succès.');
            } else {
                $this->logger->error('Erreur lors de l\'analyse du CV : ' . $result['message']);
                $analysis = null;
                $this->addFlash('danger', 'Une erreur est survenue lors de l\'analyse du CV : ' . $result['message']);
            }

            return $this->render('back_office/candidatures/view_cv.html.twig', [
                'candidature' => $candidature,
                'analysis' => $analysis
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Exception lors de l\'analyse du CV : ' . $e->getMessage());
            $this->addFlash('danger', 'Une erreur est survenue lors de l\'analyse du CV : ' . $e->getMessage());
            return $this->redirectToRoute('back.candidatures.view_cv', ['id' => $candidature->getId()]);
        }
    }



    #[Route('/{id}/postuler', name: 'app_candidature_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        OffreEmploi $offre,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $candidature = new Candidature();
        // Utilisation du formulaire sans le champ statut
        $form = $this->createForm(CandidaturePublicType::class, $candidature);
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
            try {
                // Traitement du CV
                $cvFile = $form->get('cv')->getData();
                $this->logger->info('CV file: ' . ($cvFile ? 'Présent' : 'Absent'));
                if ($cvFile) {
                    try {
                        $originalFilename = pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename.'-'.uniqid().'.'.$cvFile->guessExtension();

                        // Vérifier si le répertoire existe, sinon le créer
                        $cvDirectory = $this->getParameter('cv_directory');
                        $this->logger->info('CV directory: ' . $cvDirectory);
                        if (!file_exists($cvDirectory)) {
                            $this->logger->info('Création du répertoire CV: ' . $cvDirectory);
                            mkdir($cvDirectory, 0777, true);
                        }

                        $cvFile->move($cvDirectory, $newFilename);
                        $candidature->setCv($newFilename);
                        $this->logger->info('CV uploadé : ' . $newFilename);
                    } catch (\Exception $e) {
                        $this->logger->error('Erreur lors de l\'upload du CV: ' . $e->getMessage());
                    }
                }

                // Traitement de la lettre de motivation
                $lettreMotivationFile = $form->get('lettreMotivation')->getData();
                $this->logger->info('Lettre de motivation file: ' . ($lettreMotivationFile ? 'Présente' : 'Absente'));
                if ($lettreMotivationFile) {
                    try {
                        $originalFilename = pathinfo($lettreMotivationFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename.'-'.uniqid().'.'.$lettreMotivationFile->guessExtension();

                        // Vérifier si le répertoire existe, sinon le créer
                        $lettreMotivationDirectory = $this->getParameter('lettre_motivation_directory');
                        $this->logger->info('Lettre de motivation directory: ' . $lettreMotivationDirectory);
                        if (!file_exists($lettreMotivationDirectory)) {
                            $this->logger->info('Création du répertoire lettre de motivation: ' . $lettreMotivationDirectory);
                            mkdir($lettreMotivationDirectory, 0777, true);
                        }

                        $lettreMotivationFile->move($lettreMotivationDirectory, $newFilename);
                        $candidature->setLettreMotivation($newFilename);
                        $this->logger->info('Lettre de motivation uploadée : ' . $newFilename);
                    } catch (\Exception $e) {
                        $this->logger->error('Erreur lors de l\'upload de la lettre de motivation: ' . $e->getMessage());
                    }
                }

                $candidature->setDateCandidature(new \DateTime());
                $candidature->setOffre($offre);
                // Le statut est déjà défini à 'en_attente' dans le constructeur de l'entité

                $entityManager->persist($candidature);
                $entityManager->flush();

                $this->logger->info('Candidature créée avec succès pour l\'offre : ' . $offre->getTitre());
                $this->addFlash('success', 'Votre candidature a été envoyée avec succès !');

                // Redirection vers la page des offres d'emploi pour les candidats
                $this->logger->info('Redirection vers la page des offres d\'emploi pour les candidats');
                return $this->redirectToRoute('back.candidat.offres_emploi.index', [], 301);
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la création de la candidature : ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de votre candidature. Veuillez réessayer.');
            }
        }

        return $this->render('candidature/new.html.twig', [
            'candidature' => $candidature,
            'form' => $form->createView(),
            'offre' => $offre,
        ]);
    }

    #[Route('/{id}', name: 'app_candidature_show', methods: ['GET'])]
    public function show(Candidature $candidature): Response
    {
        return $this->render('candidature/show.html.twig', [
            'candidature' => $candidature,
        ]);
    }
}