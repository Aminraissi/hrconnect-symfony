<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\OffreEmploi;
use App\Form\CandidatureSimpleType;
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
        $candidature->setOffre($offre);
        $candidature->setStatut('en_attente');
        $candidature->setDateCandidature(new \DateTimeImmutable());

        $this->logger->info('Offre d\'emploi définie : ' . $offre->getId() . ' - ' . $offre->getTitre());

        // Utilisation du formulaire simplifié
        $form = $this->createForm(CandidatureSimpleType::class, $candidature);
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

            // Traitement du CV
            $cvFile = $form->get('cv')->getData();
            if (!$cvFile) {
                $this->addFlash('error', 'Le CV est obligatoire pour postuler à cette offre.');
                return $this->render('candidat/candidature/new.html.twig', [
                    'form' => $form->createView(),
                    'offre' => $offre,
                ]);
            }

            $newFilename = $this->uploadFile($cvFile, 'cv_directory', $slugger);
            if ($newFilename) {
                $candidature->setCv($newFilename);
            } else {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'upload du CV.');
                return $this->render('candidat/candidature/new.html.twig', [
                    'form' => $form->createView(),
                    'offre' => $offre,
                ]);
            }

            // Traitement de la lettre de motivation
            $lettreMotivationFile = $form->get('lettreMotivation')->getData();
            if (!$lettreMotivationFile) {
                $this->addFlash('error', 'La lettre de motivation est obligatoire pour postuler à cette offre.');
                return $this->render('candidat/candidature/new.html.twig', [
                    'form' => $form->createView(),
                    'offre' => $offre,
                ]);
            }

            $newFilename = $this->uploadFile($lettreMotivationFile, 'lettre_motivation_directory', $slugger);
            if ($newFilename) {
                $candidature->setLettreMotivation($newFilename);
            } else {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de la lettre de motivation.');
                return $this->render('candidat/candidature/new.html.twig', [
                    'form' => $form->createView(),
                    'offre' => $offre,
                ]);
            }

            // Enregistrer la candidature
            $this->em->persist($candidature);
            $this->em->flush();

            $this->logger->info('Candidature créée avec succès pour l\'offre : ' . $offre->getTitre());
            $this->addFlash('success', '<strong>Candidature envoyée !</strong> Votre candidature pour l\'offre "' . $offre->getTitre() . '" a été envoyée avec succès. <br>Nous vous contacterons prochainement pour vous informer de la suite du processus.');
            return $this->redirectToRoute('back.candidat.offres_emploi.index');
        }

        return $this->render('candidat/candidature/new.html.twig', [
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
