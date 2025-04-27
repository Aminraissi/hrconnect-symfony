<?php

namespace App\Controller;

use App\Entity\Absence;
use App\Form\AbsenceType;
use App\Repository\AbsenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/absence')]
final class AbsenceController extends AbstractController
{
    private $ocrApiKey = 'K89592453788957'; // Remplacez par votre clé OCR.space

    #[Route(name: 'app_absence_index', methods: ['GET'])]
    public function index(Request $request, AbsenceRepository $absenceRepository, PaginatorInterface $paginator): Response
    {
        $query = $absenceRepository->createQueryBuilder('a')
            ->leftJoin('a.employe', 'e')
            ->addSelect('e');

        $search = $request->query->get('search');
        if ($search) {
            $query->andWhere('e.nom LIKE :search OR e.prenom LIKE :search OR a.motif LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $sortField = $request->query->get('sort', 'a.date_enregistrement');
        $sortDirection = $request->query->get('direction', 'DESC');
        $query->orderBy($sortField, $sortDirection);

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('absence/index.html.twig', [
            'pagination' => $pagination,
            'sortField' => $sortField,
            'direction' => $sortDirection,
        ]);
    }
    #[Route('/new', name: 'app_absence_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $absence = new Absence();
        $absence->setDateEnregistrement(new \DateTime());
    
        $form = $this->createForm(AbsenceType::class, $absence);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('justificatif')->getData();
    
            if ($file) {
                try {
                    $filePath = $file->getPathname();
                    $mimeType = $file->getMimeType();
    
                    // Configuration optimisée pour OCR.space
                    $client = new Client([
                        'base_uri' => 'https://api.ocr.space/parse/',
                        'timeout' => 60.0,
                    ]);
    
                    $options = [
                        'multipart' => [
                            [
                                'name' => 'apikey',
                                'contents' => $this->ocrApiKey,
                            ],
                            [
                                'name' => 'language',
                                'contents' => 'fre',
                            ],
                            [
                                'name' => 'OCREngine',
                                'contents' => '2',
                            ],
                            [
                                'name' => 'detectOrientation',
                                'contents' => 'true',
                            ],
                            [
                                'name' => 'isTable',
                                'contents' => 'true',
                            ],
                            [
                                'name' => 'scale',
                                'contents' => 'true',
                            ],
                        ]
                    ];
    
                    // Gestion spécifique pour PDF ou images
                    if ($mimeType === 'application/pdf') {
                        $options['multipart'][] = [
                            'name' => 'file',
                            'contents' => fopen($filePath, 'r'),
                            'filename' => 'document.pdf'
                        ];
                        $options['multipart'][] = [
                            'name' => 'filetype',
                            'contents' => 'PDF'
                        ];
                    } else {
                        $options['multipart'][] = [
                            'name' => 'file',
                            'contents' => fopen($filePath, 'r'),
                            'filename' => 'image.' . $file->guessExtension()
                        ];
                    }
    
                    $response = $client->post('image', $options);
                    $result = json_decode($response->getBody(), true);
    
                    // Debug: Enregistrer la réponse complète dans les logs
                    file_put_contents(
                        $this->getParameter('kernel.logs_dir') . '/ocr_debug.log',
                        date('Y-m-d H:i:s') . " - " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n",
                        FILE_APPEND
                    );
    
                    // Correction: Vérification plus robuste de la réponse
                    if (!isset($result['IsErroredOnProcessing'])) {
                        throw new \Exception('Réponse inattendue de l\'API OCR');
                    }
    
                    if ($result['IsErroredOnProcessing']) {
                        $errorMessage = is_array($result['ErrorMessage']) ? 
                            implode(', ', $result['ErrorMessage']) : 
                            ($result['ErrorMessage'] ?? 'Erreur inconnue de l\'API OCR');
                        throw new \Exception($errorMessage);
                    }
    
                    if (!isset($result['ParsedResults'][0])) {
                        throw new \Exception('Aucun résultat de texte trouvé dans le document');
                    }
    
                    $text = $result['ParsedResults'][0]['ParsedText'] ?? '';
                    $text = mb_strtolower($text);
    
                    // Vérification plus robuste avec expressions régulières
                    $medicalPattern = '/(certificat|attestation|medical|m[ée]dical)/i';
                    $found = preg_match($medicalPattern, $text);
    
                    if (!$found) {
                        $this->addFlash(
                            'error',
                            'Le document doit contenir une mention médicale. ' .
                            'Termes acceptés: certification médicale, certificat médical, attestation médicale'
                        );
                        return $this->render('absence/new.html.twig', [
                            'absence' => $absence,
                            'form' => $form,
                        ]);
                    }
    
                    // Sauvegarde du fichier
                    $newFilename = uniqid() . '.' . $file->guessExtension();
                    $file->move(
                        $this->getParameter('justificatifs_directory'),
                        $newFilename
                    );
                    $absence->setJustificatif($newFilename);
    
                    $entityManager->persist($absence);
                    $entityManager->flush();
    
                    return $this->redirectToRoute('app_absence_index', [], Response::HTTP_SEE_OTHER);
    
                } catch (\Exception $e) {
                    $this->addFlash(
                        'error',
                        'Erreur technique: ' . $e->getMessage() .
                        '. Veuillez essayer avec un autre fichier ou contacter le support.'
                    );
                }
            }
        }
    
        return $this->render('absence/new.html.twig', [
            'absence' => $absence,
            'form' => $form,
        ]);
    }
    #[Route('/{id}', name: 'app_absence_show', methods: ['GET'])]
    public function show(Absence $absence): Response
    {
        return $this->render('absence/show.html.twig', [
            'absence' => $absence,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_absence_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Absence $absence, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AbsenceType::class, $absence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_absence_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('absence/edit.html.twig', [
            'absence' => $absence,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_absence_delete', methods: ['POST'])]
    public function delete(Request $request, Absence $absence, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $absence->getId(), $request->get('_token'))) {
            $entityManager->remove($absence);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_absence_index', [], Response::HTTP_SEE_OTHER);
    }
}