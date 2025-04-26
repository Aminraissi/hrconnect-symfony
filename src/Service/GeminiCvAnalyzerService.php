<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GeminiCvAnalyzerService
{
    private LoggerInterface $logger;
    private string $geminiApiKey;
    private array $criteria;
    private array $criteriaWeights;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        // Clé API Gemini (à configurer dans services.yaml ou .env)
        $this->geminiApiKey = 'AIzaSyDJC5a882ruaJN2HQR9nz_Rk_7kHHJgc-Y'; // Exemple, à remplacer par votre clé
        
        // Définition des critères d'évaluation
        $this->criteria = [
            'length' => 'Le CV peut s\'étendre sur 1, 2, 3 ou 4 pages selon le niveau d\'expérience.',
            'photo' => 'Le CV ne doit pas comporter de photo.',
            'design' => 'Le design doit être plutôt simple.',
            'colors' => 'Il est préférable d\'utiliser des couleurs sobres.',
            'content' => 'Le contenu doit mettre l\'accent sur les compétences et les expériences.',
            'readability' => 'Il doit être facile à lire et aéré.',
            'diplomas' => 'Les diplômes non canadiens doivent être expliqués avec des équivalences et des descriptions.',
            'languages' => 'La pratique de plusieurs langues doit être inscrite sur le CV.',
            'format' => 'Le CV doit être délivré au format PDF.'
        ];
        
        // Poids des critères (sur 100 points au total)
        $this->criteriaWeights = [
            'length' => 10,
            'photo' => 5,
            'design' => 10,
            'colors' => 5,
            'content' => 25,
            'readability' => 15,
            'diplomas' => 10,
            'languages' => 10,
            'format' => 10
        ];
    }

    /**
     * Analyse un CV avec l'API Gemini
     * 
     * @param string $cvPath Chemin complet vers le fichier CV
     * @return array Résultats de l'analyse avec score et détails
     */
    public function analyzeCv(string $cvPath): array
    {
        $this->logger->info('Début de l\'analyse du CV avec Gemini: ' . $cvPath);
        
        // Vérifier si le fichier existe
        if (!file_exists($cvPath)) {
            $this->logger->error('Le fichier CV n\'existe pas: ' . $cvPath);
            return [
                'success' => false,
                'score' => 0,
                'message' => 'Le fichier CV n\'existe pas',
                'details' => []
            ];
        }
        
        // Vérifier le format du fichier (PDF)
        $extension = pathinfo($cvPath, PATHINFO_EXTENSION);
        if (strtolower($extension) !== 'pdf') {
            $this->logger->warning('Le CV n\'est pas au format PDF: ' . $extension);
            // On continue l'analyse mais on pénalisera le score pour le critère 'format'
        }
        
        try {
            // Extraire le texte du CV (utiliser le service existant)
            $cvAnalyzerService = new CvAnalyzerService($this->logger, HttpClient::create());
            $extractionResult = $cvAnalyzerService->analyzeCv($cvPath);
            
            if (!$extractionResult['success']) {
                $this->logger->error('Échec de l\'extraction du texte du CV: ' . ($extractionResult['message'] ?? 'Erreur inconnue'));
                return [
                    'success' => false,
                    'score' => 0,
                    'message' => 'Échec de l\'extraction du texte du CV',
                    'details' => []
                ];
            }
            
            // Récupérer le texte brut du CV
            $cvText = $extractionResult['data']['rawText'] ?? '';
            
            if (empty($cvText)) {
                $this->logger->error('Aucun texte n\'a pu être extrait du CV');
                return [
                    'success' => false,
                    'score' => 0,
                    'message' => 'Aucun texte n\'a pu être extrait du CV',
                    'details' => []
                ];
            }
            
            // Préparer la requête pour l'API Gemini
            $prompt = $this->buildGeminiPrompt($cvText);
            
            // Appeler l'API Gemini
            $response = $this->callGeminiApi($prompt);
            
            // Analyser la réponse et calculer le score
            $analysisResults = $this->parseGeminiResponse($response);
            
            // Calculer le score total
            $totalScore = $this->calculateTotalScore($analysisResults);
            
            $this->logger->info('Analyse du CV terminée avec un score de ' . $totalScore . '%');
            
            return [
                'success' => true,
                'score' => $totalScore,
                'passed' => $totalScore >= 50, // Seuil de 50%
                'message' => $totalScore >= 50 ? 
                    'Le CV répond aux critères minimums requis.' : 
                    'Le CV ne répond pas aux critères minimums requis.',
                'details' => $analysisResults,
                'extractionData' => $extractionResult['data'] // Inclure les données extraites par le service existant
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'analyse du CV avec Gemini: ' . $e->getMessage());
            return [
                'success' => false,
                'score' => 0,
                'message' => 'Erreur lors de l\'analyse du CV: ' . $e->getMessage(),
                'details' => []
            ];
        }
    }
    
    /**
     * Construit le prompt pour l'API Gemini
     */
    private function buildGeminiPrompt(string $cvText): string
    {
        $criteriaText = implode("\n", array_map(
            fn($key, $description) => "- $key: $description", 
            array_keys($this->criteria), 
            array_values($this->criteria)
        ));
        
        return <<<EOT
        Analyse le CV suivant selon ces critères spécifiques:
        
        $criteriaText
        
        Pour chaque critère, donne une note de 0 à 10 et une explication.
        Réponds au format JSON avec cette structure:
        {
            "criteria": {
                "length": {"score": X, "explanation": "..."},
                "photo": {"score": X, "explanation": "..."},
                "design": {"score": X, "explanation": "..."},
                "colors": {"score": X, "explanation": "..."},
                "content": {"score": X, "explanation": "..."},
                "readability": {"score": X, "explanation": "..."},
                "diplomas": {"score": X, "explanation": "..."},
                "languages": {"score": X, "explanation": "..."},
                "format": {"score": X, "explanation": "..."}
            },
            "summary": "Résumé global de l'analyse"
        }
        
        Voici le contenu du CV à analyser:
        
        $cvText
        EOT;
    }
    
    /**
     * Appelle l'API Gemini
     */
    private function callGeminiApi(string $prompt): string
    {
        $this->logger->info('Appel de l\'API Gemini');
        
        try {
            $client = HttpClient::create();
            $response = $client->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent', [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'query' => [
                    'key' => $this->geminiApiKey,
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'topP' => 0.8,
                        'topK' => 40
                    ]
                ],
            ]);
            
            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new \Exception('Erreur API Gemini: ' . $statusCode);
            }
            
            $content = $response->getContent();
            $data = json_decode($content, true);
            
            // Extraire le texte de la réponse
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }
            
            throw new \Exception('Format de réponse Gemini inattendu');
            
        } catch (TransportExceptionInterface $e) {
            throw new \Exception('Erreur de communication avec l\'API Gemini: ' . $e->getMessage());
        }
    }
    
    /**
     * Analyse la réponse de l'API Gemini
     */
    private function parseGeminiResponse(string $response): array
    {
        $this->logger->info('Analyse de la réponse Gemini');
        
        // Extraction du JSON de la réponse (peut être entouré de texte)
        preg_match('/\{.*\}/s', $response, $matches);
        
        if (empty($matches)) {
            $this->logger->error('Impossible de trouver du JSON dans la réponse Gemini');
            
            // Simuler une réponse par défaut
            return [
                'length' => ['score' => 5, 'explanation' => 'Impossible d\'évaluer le nombre de pages.'],
                'photo' => ['score' => 5, 'explanation' => 'Impossible de déterminer si le CV contient une photo.'],
                'design' => ['score' => 5, 'explanation' => 'Impossible d\'évaluer le design.'],
                'colors' => ['score' => 5, 'explanation' => 'Impossible d\'évaluer les couleurs.'],
                'content' => ['score' => 5, 'explanation' => 'Impossible d\'évaluer le contenu.'],
                'readability' => ['score' => 5, 'explanation' => 'Impossible d\'évaluer la lisibilité.'],
                'diplomas' => ['score' => 5, 'explanation' => 'Impossible d\'évaluer les diplômes.'],
                'languages' => ['score' => 5, 'explanation' => 'Impossible d\'évaluer les langues.'],
                'format' => ['score' => 5, 'explanation' => 'Impossible d\'évaluer le format.']
            ];
        }
        
        $jsonStr = $matches[0];
        $data = json_decode($jsonStr, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Erreur de parsing JSON: ' . json_last_error_msg());
            throw new \Exception('Erreur de parsing JSON: ' . json_last_error_msg());
        }
        
        return $data['criteria'] ?? [];
    }
    
    /**
     * Calcule le score total en fonction des poids des critères
     */
    private function calculateTotalScore(array $analysisResults): float
    {
        $totalScore = 0;
        $totalWeight = array_sum($this->criteriaWeights);
        
        foreach ($this->criteriaWeights as $criterion => $weight) {
            if (isset($analysisResults[$criterion]['score'])) {
                $score = $analysisResults[$criterion]['score'];
                // Convertir le score de 0-10 en pourcentage du poids
                $weightedScore = ($score / 10) * $weight;
                $totalScore += $weightedScore;
            }
        }
        
        // Convertir en pourcentage
        return round(($totalScore / $totalWeight) * 100);
    }
}
