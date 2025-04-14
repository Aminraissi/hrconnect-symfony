<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\OffreEmploi;
use App\Form\CandidatureSimpleType;
use App\Form\CandidatureSimpleNewType;
use App\Entity\Candidat;
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
        SluggerInterface $slugger
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
            } else {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'upload du CV.');
                return $this->render('candidat/candidature/new_simple.html.twig', [
                    'form' => $form->createView(),
                    'offre' => $offre,
                ]);
            }

            // Note: La lettre de motivation n'est plus utilisée dans la nouvelle structure

            // Enregistrer la candidature
            $this->em->persist($candidature);
            $this->em->flush();

            $this->logger->info('Candidature créée avec succès pour l\'offre : ' . $offre->getTitle());
            $this->addFlash('success', '<strong>Candidature envoyée !</strong> Votre candidature pour l\'offre "' . $offre->getTitle() . '" a été envoyée avec succès. <br>Nous vous contacterons prochainement pour vous informer de la suite du processus.');
            return $this->redirectToRoute('back.candidat.offres_emploi.index');
        }

        return $this->render('candidat/candidature/new_simple.html.twig', [
            'form' => $form->createView(),
            'offre' => $offre,
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
