<?php
namespace App\Controller;

use App\Entity\Formation;
use App\Form\FormationType;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/backoffice/formations')]
final class FormationController extends AbstractController
{
    #[Route(name: 'app_formations_index', methods: ['GET'])]
    public function index(Request $request, FormationRepository $formationRepository, PaginatorInterface $paginator): Response
    {
        $type = $request->query->get('type');

        if ($type == 'free') {
            $query = $formationRepository->createQueryBuilder('f')
                ->where('f.price = 0')
                ->getQuery();
        } elseif ($type == 'paid') {
            $query = $formationRepository->findPaidFormations();
        } else {
            $query = $formationRepository->createQueryBuilder('f')
                ->getQuery();
        }

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            7
        );

        return $this->render('formations/index.html.twig', [
            'formations' => $pagination, // Now paginated
            'type'       => $type,
        ]);
    }

    #[Route('/new', name: 'app_formations_new', methods: ['GET', 'POST'])]
    public function new (Request $request, EntityManagerInterface $entityManager): Response
    {
        $formation = new Formation();
        $form      = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $file = $request->files->get('image');

            if ($file == null) {
                $this->addFlash('danger', 'Choisir une image');
                return $this->render('formations/new.html.twig', [
                    'formation' => $formation,
                    'form'      => $form,
                ]);
            }

            $imageContent = file_get_contents($file->getPathname());

            $apiKey = $_ENV['IMG_BB_API_KEY'];
            $url    = 'https://api.imgbb.com/1/upload?key=' . $apiKey;
            $data   = [
                'image' => base64_encode($imageContent),
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $responseData = json_decode($response, true);
            if (isset($responseData['data']['url'])) {
                $imageUrl = $responseData['data']['url'];
                $formation->setImage($imageUrl);
            }

            $entityManager->persist($formation);
            $entityManager->flush();

            $this->addFlash('success', 'The training has been created successfully.');

            return $this->redirectToRoute('app_formations_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('formations/new.html.twig', [
            'formation' => $formation,
            'form'      => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_formations_show', methods: ['GET'])]
    public function show(Formation $formation): Response
    {
        return $this->render('formations/show.html.twig', [
            'formation' => $formation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_formations_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Formation $formation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $file = $request->files->get('image');

            if ($file != null) {
                $imageContent = file_get_contents($file->getPathname());

                $apiKey = $_ENV['IMG_BB_API_KEY'];
                $url    = 'https://api.imgbb.com/1/upload?key=' . $apiKey;
                $data   = [
                    'image' => base64_encode($imageContent),
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);

                $responseData = json_decode($response, true);
                if (isset($responseData['data']['url'])) {
                    $imageUrl = $responseData['data']['url'];
                    $formation->setImage($imageUrl);
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_formations_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('formations/edit.html.twig', [
            'formation' => $formation,
            'form'      => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_formations_delete', methods: ['POST'])]
    public function delete(Request $request, Formation $formation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $formation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($formation);
            $entityManager->flush();
        }

        $this->addFlash('success', 'The training has been deleted successfully.');

        return $this->redirectToRoute('app_formations_index', [], Response::HTTP_SEE_OTHER);
    }
}
