<?php

namespace App\Controller;

use App\Service\SpellCheckerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

class SpellCheckerController extends AbstractController
{
    private LoggerInterface $logger;
    private SpellCheckerService $spellCheckerService;
    
    public function __construct(LoggerInterface $logger, SpellCheckerService $spellCheckerService)
    {
        $this->logger = $logger;
        $this->spellCheckerService = $spellCheckerService;
    }
    
    #[Route('/api/spell-check', name: 'api.spell_check', methods: ['POST'])]
    public function checkSpelling(Request $request): Response
    {
        $text = $request->request->get('text');
        $language = $request->request->get('language', 'fr');
        
        $this->logger->info('Requête de vérification orthographique reçue');
        $this->logger->info('Texte à vérifier: ' . substr($text, 0, 50) . '...');
        
        if (empty($text)) {
            $this->logger->warning('Texte vide reçu');
            return new JsonResponse(['error' => 'Le texte est requis'], Response::HTTP_BAD_REQUEST);
        }
        
        try {
            $corrections = $this->spellCheckerService->checkSpelling($text, $language);
            
            $this->logger->info('Corrections trouvées: ' . count($corrections));
            
            return new JsonResponse([
                'success' => true,
                'corrections' => $corrections,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification orthographique: ' . $e->getMessage());
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Une erreur est survenue lors de la vérification orthographique: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
