<?php
namespace App\Controller;

use App\Entity\OffreEmploi;
use App\Form\OffreEmploiType;
use App\Repository\OffreEmploiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

#[Route('/back-office/offres-emploi')]
class OffreEmploiController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $em
    ) {
    }

    #[Route('/', name: 'back.offres_emploi.index')]
    public function index(OffreEmploiRepository $repository): Response
    {
        try {
            // Utiliser l'ID au lieu de datePublication pour le tri
            $offres = $repository->findBy([], ['id' => 'DESC']);
            $this->logger->info('Nombre d\'offres récupérées : ' . count($offres));

            return $this->render('back_office/offres_emploi/index.html.twig', [
                'offres' => $offres,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des offres : ' . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors de la récupération des offres.');
            return $this->render('back_office/offres_emploi/index.html.twig', [
                'offres' => [],
            ]);
        }
    }

    #[Route('/add', name: 'back.offres_emploi.add', methods: ['GET', 'POST'])]
    public function add(Request $request): Response
    {
        $offre = new OffreEmploi();
        $form = $this->createForm(OffreEmploiType::class, $offre);

        if ($request->isMethod('POST')) {
            $this->logger->info('Requête POST reçue pour ajouter une offre');

            $data = $request->request->all('offre_emploi');

            if (isset($data)) {
                $offre->setTitre($data['titre'] ?? '');
                $offre->setTypeContrat($data['typeContrat'] ?? '');
                $offre->setLocalisation($data['localisation'] ?? '');
                $offre->setSalaire($data['salaire'] ?? '');
                $offre->setDescription($data['description'] ?? '');
                $offre->setProfilRecherche($data['profilRecherche'] ?? '');
                $offre->setAvantages($data['avantages'] ?? null);
                $offre->setIsActive(isset($data['isActive']) && $data['isActive'] === '1');
                $offre->setDatePublication(new \DateTime());

                try {
                    $this->em->persist($offre);
                    $this->em->flush();

                    $this->addFlash('success', 'L\'offre d\'emploi a été créée avec succès');
                    return $this->redirectToRoute('back.offres_emploi.index');
                } catch (\Exception $e) {
                    $this->logger->error('Erreur lors de la création : ' . $e->getMessage());
                    $this->addFlash('error', 'Une erreur est survenue lors de la création de l\'offre');
                }
            }
        }

        return $this->render('back_office/offres_emploi/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'back.offres_emploi.edit', methods: ['GET', 'POST'])]
    public function edit(OffreEmploi $offre, Request $request): Response
    {
        try {
            $form = $this->createForm(OffreEmploiType::class, $offre);

            if ($request->isMethod('POST')) {
                $this->logger->info('Requête POST reçue pour modifier une offre');

                $data = $request->request->all('offre_emploi');

                if (isset($data)) {
                    $offre->setTitre($data['titre'] ?? '');
                    $offre->setTypeContrat($data['typeContrat'] ?? '');
                    $offre->setLocalisation($data['localisation'] ?? '');
                    $offre->setSalaire($data['salaire'] ?? '');
                    $offre->setDescription($data['description'] ?? '');
                    $offre->setProfilRecherche($data['profilRecherche'] ?? '');
                    $offre->setAvantages($data['avantages'] ?? null);
                    $offre->setIsActive(isset($data['isActive']) && $data['isActive'] === '1');

                    try {
                        $this->em->flush();

                        $this->logger->info('Offre modifiée : ' . $offre->getTitre());
                        $this->addFlash('success', 'L\'offre d\'emploi a été modifiée avec succès');
                        return $this->redirectToRoute('back.offres_emploi.index');
                    } catch (\Exception $e) {
                        $this->logger->error('Erreur lors de la modification : ' . $e->getMessage());
                        $this->addFlash('error', 'Une erreur est survenue lors de la modification de l\'offre');
                    }
                }
            }

            return $this->render('back_office/offres_emploi/edit.html.twig', [
                'form' => $form->createView(),
                'offre' => $offre,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la modification de l\'offre : ' . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors de la modification de l\'offre.');
            return $this->redirectToRoute('back.offres_emploi.index');
        }
    }

    #[Route('/{id}/delete', name: 'back.offres_emploi.delete', methods: ['GET', 'POST'])]
    public function delete(OffreEmploi $offre): Response
    {
        try {
            $this->em->remove($offre);
            $this->em->flush();

            $this->logger->info('Offre supprimée : ' . $offre->getTitre());
            $this->addFlash('success', 'L\'offre d\'emploi a été supprimée avec succès');
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression de l\'offre : ' . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression de l\'offre.');
        }

        return $this->redirectToRoute('back.offres_emploi.index');
    }

    #[Route('/{id}/show', name: 'back.offres_emploi.show', methods: ['GET'])]
    public function show(OffreEmploi $offre): Response
    {
        try {
            return $this->render('back_office/offres_emploi/show.html.twig', [
                'offre' => $offre,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'affichage de l\'offre : ' . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors de l\'affichage de l\'offre');
            return $this->redirectToRoute('back.offres_emploi.index');
        }
    }
}