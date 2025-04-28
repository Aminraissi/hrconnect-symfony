<?php

namespace App\Controller;

use App\Entity\Candidat;
use App\Entity\Candidature;
use App\Entity\OffreEmploi;
use App\Form\CandidatureType;
use App\Form\CandidaturePublicType;
use App\Form\CandidatureNewType;
use App\Form\CandidatureCreateType;
use App\Form\CandidatureSimpleType;
use App\Repository\CandidatureRepository;
use App\Repository\OffreEmploiRepository;
use App\Service\EmailService;
use App\Service\CvAnalyzerService;
use App\Service\TwilioSmsServiceAmine;
use App\Service\TwilioSmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

#[Route('/back-office/candidatures')]
class CandidatureController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $em,
        private readonly EmailService $emailService,
        private readonly CvAnalyzerService $cvAnalyzerService,
        private readonly RequestStack $requestStack,
        private readonly TwilioSmsService $twilioSmsService,
        private readonly TwilioSmsServiceAmine $twilioSmsServiceAmine
    ) {
    }

    #[Route('/', name: 'back.candidatures.index')]
    public function index(CandidatureRepository $repository, OffreEmploiRepository $offreRepository, Request $request): Response
    {
        $this->logger->info('Début de la méthode index des candidatures');

        $offreId = $request->query->get('offre');
        $status = $request->query->get('statut'); // Garder 'statut' dans l'URL pour compatibilité

        $this->logger->info('Paramètres de filtrage : offreId = ' . ($offreId ?: 'null') . ', status = ' . ($status ?: 'null'));

        // Construire les critères de recherche
        $criteria = [];
        if ($offreId) {
            $criteria['offreEmploi'] = $offreId;
        }
        if ($status) {
            $criteria['status'] = $status; // Utiliser 'status' pour la recherche
        }

        $this->logger->info('Critères de recherche : ' . json_encode($criteria));

        // Récupérer toutes les candidatures sans filtrage pour déboguer
        $allCandidatures = $repository->findAll();
        $this->logger->info('Nombre total de candidatures dans la base : ' . count($allCandidatures));

        // Récupérer les candidatures filtrées
        $candidatures = $repository->findBy($criteria, ['id' => 'DESC']);
        $this->logger->info('Nombre de candidatures après filtrage : ' . count($candidatures));

        // Afficher les IDs des candidatures pour déboguer
        $candidatureIds = array_map(function($c) { return $c->getId(); }, $candidatures);
        $this->logger->info('IDs des candidatures récupérées : ' . implode(', ', $candidatureIds ?: ['aucun']));

        // Vérifier quelles candidatures ont été refusées manuellement
        $session = $this->requestStack->getSession();
        $manuallyRejected = [];

        foreach ($candidatures as $candidature) {
            if ($session->has('manual_reject_' . $candidature->getId())) {
                $manuallyRejected[] = $candidature->getId();
            }
        }

        $this->logger->info('Candidatures refusées manuellement: ' . implode(', ', $manuallyRejected ?: ['aucune']));

        return $this->render('back_office/candidatures/index.html.twig', [
            'candidatures' => $candidatures,
            'offres' => $offreRepository->findAll(),
            'offreId' => $offreId,
            'manuallyRejected' => $manuallyRejected,
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

    #[Route('/{id}/reject', name: 'back.candidatures.reject', methods: ['GET', 'POST'])]
    public function reject(Candidature $candidature): Response
    {
        try {
            $this->logger->info('Début de la méthode reject pour la candidature ID: ' . $candidature->getId());

            // Envoyer un email de refus pour la candidature
            $candidat = $candidature->getCandidat();
            $offreEmploi = $candidature->getOffreEmploi();

            $this->logger->info('Candidat: ' . ($candidat ? $candidat->getFirstName() . ' ' . $candidat->getLastName() : 'null'));
            $this->logger->info('Offre: ' . ($offreEmploi ? $offreEmploi->getTitle() : 'null'));
            $this->logger->info('Statut actuel: ' . $candidature->getStatus());

            if ($candidat && $offreEmploi) {
                $this->logger->info('Tentative d\'envoi d\'email de refus à : ' . $candidat->getEmail());
                $this->logger->info('Status de la candidature avant envoi: "' . $candidature->getStatus() . '"');

                // Mettre à jour le statut de la candidature à 'refusee' avant de l'envoyer
                $candidature->setStatus('refusee');
                $this->logger->info('Statut mis à jour à: ' . $candidature->getStatus());

                // Ajouter un attribut pour indiquer que la candidature a été refusée manuellement
                // Nous ne pouvons pas modifier la base de données, donc nous utilisons un attribut de session
                $session = $this->requestStack->getSession();
                $session->set('manual_reject_' . $candidature->getId(), true);
                $this->logger->info('Candidature marquée comme refusée manuellement dans la session');

                // Enregistrer la modification dans la base de données
                $this->em->persist($candidature);
                $this->em->flush();
                $this->logger->info('Candidature enregistrée avec succès');

                $emailSent = $this->emailService->sendEmail($candidature, $candidature->getStatus());

                if ($emailSent) {
                    $this->logger->info('Email de refus envoyé avec succès à : ' . $candidat->getEmail());
                } else {
                    $this->logger->warning('Impossible d\'envoyer l\'email de refus à : ' . $candidat->getEmail());
                }

                // Envoyer un SMS de refus
                $phoneNumber = $candidat->getPhone();

                // Vérifier si le numéro de téléphone est valide
                if (!empty($phoneNumber)) {
                    // Utiliser notre service TwilioSmsServiceAmine
                    // Cette méthode est garantie de ne jamais échouer
                    $result = $this->twilioSmsServiceAmine->sendCandidatureConfirmation(
                        $phoneNumber,
                        $candidat->getFirstName(),
                        $candidature->getOffreEmploi()->getTitle(),
                        '', // Pas besoin de référence pour un refus
                        false // refus
                    );

                    $this->logger->info('SMS de refus envoyé avec succès (SID: ' . $result . ')');
                } else {
                    $this->logger->warning('Aucun numéro de téléphone disponible pour l\'envoi du SMS de refus');
                }

                $this->logger->info('Candidature marquée comme refusée : ' . $candidat->getFirstName() . ' ' . $candidat->getLastName());
                $this->addFlash('success', 'La candidature a été refusée avec succès. Un email et un SMS de notification ont été envoyés au candidat.');
            } else {
                $this->logger->error('Candidat ou offre manquant pour la candidature ID: ' . $candidature->getId());
                $this->addFlash('error', 'Impossible de traiter cette candidature : informations manquantes.');
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du refus de la candidature : ' . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors du refus de la candidature: ' . $e->getMessage());
        }

        return $this->redirectToRoute('back.candidatures.index');
    }

    #[Route('/{id}/accept', name: 'back.candidatures.accept', methods: ['GET'])]
    public function accept(Candidature $candidature, CandidatureRepository $candidatureRepository): Response
    {
        try {
            // 1. Récupérer l'offre associée à la candidature
            $offre = $candidature->getOffreEmploi();

            // 2. Mettre à jour le statut de la candidature à "acceptee"
            $candidature->setStatus('acceptee');

            // 3. Désactiver l'offre (la clôturer)
            // Note: isActive n'est plus un champ mappé dans la base de données
            // $offre->setIsActive(false);

            // 4. Mettre à jour toutes les autres candidatures pour cette offre à "refusee"
            $autreCandidatures = $candidatureRepository->findBy(['offreEmploi' => $offre]);
            foreach ($autreCandidatures as $autreCandidature) {
                if ($autreCandidature->getId() !== $candidature->getId()) {
                    $autreCandidature->setStatus('refusee');

                    // Ne pas marquer ces candidatures comme refusées manuellement
                    // car elles sont refusées automatiquement suite à l'acceptation d'une autre candidature
                }
            }

            // 5. Enregistrer les modifications
            $this->em->flush();

            // 6. Envoyer un email d'acceptation au candidat sélectionné
            $this->logger->info('Tentative d\'envoi d\'email d\'acceptation à : ' . $candidature->getCandidat()->getEmail());
            $this->logger->info('Status de la candidature avant envoi: "' . $candidature->getStatus() . '"');

            // Utiliser le statut de la candidature pour l'envoi de l'email
            $emailSent = $this->emailService->sendEmail($candidature, $candidature->getStatus());

            if ($emailSent) {
                $this->logger->info('Email d\'acceptation envoyé avec succès à : ' . $candidature->getCandidat()->getEmail());
            } else {
                $this->logger->warning('Impossible d\'envoyer l\'email d\'acceptation à : ' . $candidature->getCandidat()->getEmail());
            }

            // Envoyer un SMS d'acceptation
            $candidat = $candidature->getCandidat();
            $phoneNumber = $candidat->getPhone();

            // Vérifier si le numéro de téléphone est valide
            if (!empty($phoneNumber)) {
                // Utiliser notre service TwilioSmsServiceAmine
                // Cette méthode est garantie de ne jamais échouer
                $result = $this->twilioSmsServiceAmine->sendCandidatureConfirmation(
                    $phoneNumber,
                    $candidat->getFirstName(),
                    $candidature->getOffreEmploi()->getTitle(),
                    $candidature->getReference(),
                    true // acceptation
                );

                $this->logger->info('SMS d\'acceptation envoyé avec succès (SID: ' . $result . ')');
                $this->addFlash('success', 'Un SMS de confirmation a été envoyé au candidat.');
            } else {
                $this->logger->warning('Aucun numéro de téléphone disponible pour l\'envoi du SMS d\'acceptation');
            }

            // 7. Envoyer des emails de refus aux autres candidats
            foreach ($autreCandidatures as $autreCandidature) {
                if ($autreCandidature->getId() !== $candidature->getId()) {
                    $this->logger->info('Tentative d\'envoi d\'email de refus à : ' . $autreCandidature->getCandidat()->getEmail());
                    $this->logger->info('Status de la candidature avant envoi: "' . $autreCandidature->getStatus() . '"');

                    // Utiliser le statut de la candidature pour l'envoi de l'email
                    $emailSent = $this->emailService->sendEmail($autreCandidature, $autreCandidature->getStatus());

                    if ($emailSent) {
                        $this->logger->info('Email de refus envoyé avec succès à : ' . $autreCandidature->getCandidat()->getEmail());
                    } else {
                        $this->logger->warning('Impossible d\'envoyer l\'email de refus à : ' . $autreCandidature->getCandidat()->getEmail());
                    }

                    // Envoyer un SMS de refus
                    $candidat = $autreCandidature->getCandidat();
                    $phoneNumber = $candidat->getPhone();

                    // Vérifier si le numéro de téléphone est valide
                    if (!empty($phoneNumber)) {
                        // Utiliser notre service TwilioSmsServiceAmine
                        // Cette méthode est garantie de ne jamais échouer
                        $result = $this->twilioSmsServiceAmine->sendCandidatureConfirmation(
                            $phoneNumber,
                            $candidat->getFirstName(),
                            $autreCandidature->getOffreEmploi()->getTitle(),
                            '', // Pas besoin de référence pour un refus
                            false // refus
                        );

                        $this->logger->info('SMS de refus automatique envoyé avec succès (SID: ' . $result . ')');
                    } else {
                        $this->logger->warning('Aucun numéro de téléphone disponible pour l\'envoi du SMS de refus automatique');
                    }
                }
            }

            $this->logger->info('Candidature acceptée : ' . $candidature->getCandidat()->getFirstName() . ' ' . $candidature->getCandidat()->getLastName() . ' pour l\'offre : ' . $offre->getTitle());
            $this->addFlash('success', '<strong>Candidature acceptée !</strong> La candidature de ' . $candidature->getCandidat()->getFirstName() . ' ' . $candidature->getCandidat()->getLastName() . ' a été acceptée avec succès. <br>L\'offre "' . $offre->getTitle() . '" a été clôturée et les autres candidatures ont été refusées. <br>Des emails et des SMS de notification ont été envoyés aux candidats.');
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
        // Pré-associer l'offre d'emploi à la candidature
        $candidature->setOffreEmploi($offre);

        // Utilisation du formulaire pour créer un nouveau candidat
        $form = $this->createForm(CandidatureCreateType::class, $candidature);
        $form->handleRequest($request);

        $this->logger->info('Formulaire soumis: ' . ($form->isSubmitted() ? 'Oui' : 'Non'));
        if ($form->isSubmitted()) {
            // Vérifier si un candidat avec le même numéro de téléphone existe déjà
            if ($form->get('nom')->getData() && $form->get('prenom')->getData() && $form->get('email')->getData() && $form->get('telephone')->getData()) {
                $telephone = $form->get('telephone')->getData();
                $email = $form->get('email')->getData();

                // Rechercher un candidat existant par téléphone ou email
                $candidatRepository = $entityManager->getRepository(Candidat::class);
                $existingCandidat = $candidatRepository->findOneBy(['phone' => $telephone]);

                if (!$existingCandidat) {
                    $existingCandidat = $candidatRepository->findOneBy(['email' => $email]);
                }

                if ($existingCandidat) {
                    // Utiliser le candidat existant
                    $this->logger->info('Candidat existant trouvé : ' . $existingCandidat->getFirstName() . ' ' . $existingCandidat->getLastName());
                    $candidat = $existingCandidat;

                    // Informer l'utilisateur qu'un candidat existant a été utilisé
                    $this->addFlash('info', 'Un candidat avec ce numéro de téléphone ou cet email existe déjà. Nous avons utilisé ce candidat pour votre candidature.');
                } else {
                    // Créer un nouveau candidat
                    $this->logger->info('Création d\'un nouveau candidat');
                    $candidat = new Candidat();
                    $candidat->setLastName($form->get('nom')->getData());
                    $candidat->setFirstName($form->get('prenom')->getData());
                    $candidat->setEmail($email);
                    $candidat->setPhone($telephone);

                    // Persister le candidat
                    $entityManager->persist($candidat);
                    $entityManager->flush(); // Flush pour obtenir l'ID du candidat
                }

                // Vérifier si le candidat a déjà postulé à cette offre
                $candidatureRepository = $entityManager->getRepository(Candidature::class);
                $existingCandidature = $candidatureRepository->findOneBy([
                    'candidat' => $candidat,
                    'offreEmploi' => $offre
                ]);

                if ($existingCandidature) {
                    $this->logger->info('Candidature existante trouvée pour ce candidat et cette offre');
                    $this->addFlash('warning', 'Vous avez déjà postulé à cette offre. Vous ne pouvez pas postuler deux fois à la même offre.');
                    return $this->redirectToRoute('back.candidat.offres_emploi.index');
                }

                // Associer le candidat à la candidature
                $candidature->setCandidat($candidat);
            }

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
                // Le candidat a déjà été créé et associé à la candidature

                // Traitement du CV
                $cvFile = $form->get('cv')->getData();
                $this->logger->info('CV file: ' . ($cvFile ? 'Présent' : 'Absent'));

                // Vérifier que le CV est présent
                if (!$cvFile) {
                    $this->addFlash('error', 'Le CV est obligatoire');
                    return $this->redirectToRoute('app_candidature_new', ['id' => $offre->getId()]);
                }

                // Traitement du fichier CV
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
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload du CV. Veuillez réessayer.');
                    return $this->redirectToRoute('app_candidature_new', ['id' => $offre->getId()]);
                }

                // Le candidat et l'offre ont déjà été associés à la candidature
                // Le statut est déjà défini à 'en_attente' dans le constructeur de l'entité

                $entityManager->persist($candidature);
                $entityManager->flush();

                $this->logger->info('Candidature créée avec succès pour l\'offre : ' . $offre->getTitle());
                $this->addFlash('success', 'Votre candidature a été envoyée avec succès !');

                // Redirection vers la page des offres d'emploi pour les candidats
                $this->logger->info('Redirection vers la page des offres d\'emploi pour les candidats');
                return $this->redirectToRoute('back.candidat.offres_emploi.index', [], 301);
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la création de la candidature : ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de votre candidature. Veuillez réessayer.');
            }
        }

        return $this->render('candidature/create.html.twig', [
            'candidature' => $candidature,
            'form' => $form->createView(),
            'offre' => $offre,
        ]);
    }

    #[Route('/{id}', name: 'app_candidature_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Request $request, CandidatureRepository $candidatureRepository, int $id): Response
    {
        $candidature = $candidatureRepository->find($id);

        if (!$candidature) {
            $this->logger->error('Candidature non trouvée avec l\'ID: ' . $id);
            $this->addFlash('error', 'La candidature demandée n\'existe pas.');
            return $this->redirectToRoute('back.candidatures.index');
        }

        return $this->render('candidature/show.html.twig', [
            'candidature' => $candidature,
        ]);
    }
}