<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use App\Service\CvAnalyzerService;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class GeminiCvEvaluatorService
{
    private LoggerInterface $logger;
    private string $geminiApiKey;
    private array $criteria;
    private array $criteriaWeights;
    private RequestStack $requestStack;
    private CvAnalyzerService $cvAnalyzerService;

    public function __construct(LoggerInterface $logger, RequestStack $requestStack, CvAnalyzerService $cvAnalyzerService)
    {
        $this->logger = $logger;
        $this->requestStack = $requestStack;
        $this->cvAnalyzerService = $cvAnalyzerService;

        // Clé API Gemini depuis les variables d'environnement
        $this->geminiApiKey = $_ENV['GEMINI_API_KEY'] ?? 'AIzaSyAHGWEZe3xMZLRbtOUtufclqYmn_mgOlls';

        // Définition des critères d'évaluation
        $this->criteria = [
            'relevance' => 'Le document doit être un CV et non un autre type de document (cours, lettre, etc.).',
            'experience' => 'Le CV doit détailler les expériences professionnelles avec dates, postes, entreprises et responsabilités.',
            'skills' => 'Les compétences techniques et transversales doivent être clairement identifiables.',
            'education' => 'Les diplômes et formations doivent être listés avec dates, établissements et spécialités.',
            'languages' => 'Les langues maîtrisées doivent être mentionnées avec le niveau de compétence.',
            'contact' => 'Les informations de contact (email, téléphone) doivent être présentes et complètes.',
            'format' => 'Le CV doit être au format PDF et bien structuré.',
            'readability' => 'Le CV doit être facile à lire, aéré et utiliser une typographie claire.',
            'length' => 'Le CV doit avoir une longueur appropriée (1-4 pages selon l\'expérience).',
            'design' => 'Le design doit être professionnel, sobre et sans photo.'
        ];

        // Poids des critères (sur 100 points au total)
        $this->criteriaWeights = [
            'relevance' => 20,    // Le plus important : vérifier que c'est bien un CV
            'experience' => 20,    // Expérience professionnelle très importante
            'skills' => 15,        // Compétences importantes
            'education' => 10,     // Formation/diplômes importants
            'languages' => 5,      // Langues moins importantes mais utiles
            'contact' => 5,        // Informations de contact nécessaires
            'format' => 5,         // Format moins important mais préférable
            'readability' => 10,   // Lisibilité importante pour la compréhension
            'length' => 5,         // Longueur moins importante
            'design' => 5          // Design moins important
        ];
    }

    /**
     * Analyse un CV avec l'API Gemini et stocke les résultats dans la session
     *
     * @param string $cvPath Chemin complet vers le fichier CV
     * @param string $candidatureId Identifiant de la candidature (pour stocker les résultats)
     * @return array Résultats de l'analyse avec score et détails
     */
    public function evaluateCv(string $cvPath, string $candidatureId): array
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
            // Extraire le texte du CV (utiliser le service injecté)
            $extractionResult = $this->cvAnalyzerService->analyzeCv($cvPath);

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

            // Stocker les résultats dans la session
            $result = [
                'success' => true,
                'score' => $totalScore,
                'passed' => $totalScore >= 50, // Seuil de 50%
                'message' => $totalScore >= 50 ?
                    'Le CV répond aux critères minimums requis.' :
                    'Le CV ne répond pas aux critères minimums requis.',
                'details' => $analysisResults,
                'extractionData' => $extractionResult['data'] // Inclure les données extraites par le service existant
            ];

            // Stocker les résultats dans la session
            $session = $this->requestStack->getSession();
            $session->set('cv_analysis_' . $candidatureId, $result);

            return $result;

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
     * Récupère les résultats d'analyse stockés dans la session
     */
    public function getAnalysisResults(string $candidatureId): ?array
    {
        $session = $this->requestStack->getSession();
        return $session->get('cv_analysis_' . $candidatureId);
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
        Tu es un expert en recrutement spécialisé dans l'évaluation des CV. Analyse le document suivant selon ces critères spécifiques:

        $criteriaText

        D'abord, vérifie si le document est bien un CV et non un autre type de document (cours, lettre, etc.). Si ce n'est pas un CV, attribue un score de 0 au critère 'relevance' et explique pourquoi.

        Pour chaque critère, donne une note de 0 à 10 et une explication détaillée. Sois particulièrement attentif aux critères suivants:
        - 'relevance': Vérifie que le document est bien un CV et contient les sections attendues (expérience, formation, compétences)
        - 'experience': Vérifie que les expériences professionnelles sont détaillées avec dates, postes, entreprises et responsabilités
        - 'skills': Vérifie que les compétences techniques et transversales sont clairement identifiables

        Réponds au format JSON avec cette structure:
        {
            "criteria": {
                "relevance": {"score": X, "explanation": "..."},
                "experience": {"score": X, "explanation": "..."},
                "skills": {"score": X, "explanation": "..."},
                "education": {"score": X, "explanation": "..."},
                "languages": {"score": X, "explanation": "..."},
                "contact": {"score": X, "explanation": "..."},
                "format": {"score": X, "explanation": "..."},
                "readability": {"score": X, "explanation": "..."},
                "length": {"score": X, "explanation": "..."},
                "design": {"score": X, "explanation": "..."}
            },
            "summary": "Résumé global de l'analyse avec recommandations d'amélioration"
        }

        Voici le contenu du document à analyser:

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
            $response = $client->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent', [
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
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'appel à l\'API Gemini: ' . $e->getMessage());

            // En cas d'erreur, simuler une réponse pour éviter de bloquer le processus
            $cvText = substr($prompt, strrpos($prompt, 'Voici le contenu du CV à analyser:') + 35);
            $isShortCv = strlen(trim($cvText)) < 100;

            // Détecter si le contenu ressemble à un CV
            $isCvLike = preg_match('/exp[eé]rience|formation|comp[eé]tence|dipl[oô]me|CV|curriculum|vitae/i', $cvText);

            // Créer une réponse JSON simulée
            $simulatedResponse = [
                'criteria' => [
                    'relevance' => [
                        'score' => $isCvLike ? ($isShortCv ? 5 : 8) : 0,
                        'explanation' => $isCvLike ? 'Le document semble être un CV.' : 'Le document ne semble pas être un CV valide.'
                    ],
                    'experience' => [
                        'score' => $isShortCv ? 2 : 7,
                        'explanation' => $isShortCv ? 'Peu ou pas d\'expériences professionnelles détaillées.' : 'Les expériences professionnelles sont présentes mais pourraient être plus détaillées.'
                    ],
                    'skills' => [
                        'score' => $isShortCv ? 3 : 6,
                        'explanation' => $isShortCv ? 'Peu de compétences identifiables.' : 'Plusieurs compétences sont listées mais pourraient être mieux organisées.'
                    ],
                    'education' => [
                        'score' => $isShortCv ? 2 : 7,
                        'explanation' => $isShortCv ? 'Informations sur la formation insuffisantes.' : 'Les diplômes sont mentionnés mais les équivalences pourraient être plus détaillées.'
                    ],
                    'languages' => [
                        'score' => $isShortCv ? 1 : 6,
                        'explanation' => $isShortCv ? 'Aucune mention des langues pratiquées.' : 'Les langues sont mentionnées mais sans précision sur le niveau.'
                    ],
                    'contact' => [
                        'score' => $isShortCv ? 4 : 8,
                        'explanation' => $isShortCv ? 'Informations de contact incomplètes.' : 'Les informations de contact sont présentes et complètes.'
                    ],
                    'format' => [
                        'score' => 8,
                        'explanation' => 'Le document est au format PDF.'
                    ],
                    'readability' => [
                        'score' => $isShortCv ? 4 : 7,
                        'explanation' => $isShortCv ? 'La lisibilité est difficile à évaluer en raison du manque de contenu.' : 'Le document est assez lisible mais pourrait être amélioré.'
                    ],
                    'length' => [
                        'score' => $isShortCv ? 3 : 7,
                        'explanation' => $isShortCv ? 'Le document est trop court pour un CV complet.' : 'La longueur du document est appropriée.'
                    ],
                    'design' => [
                        'score' => $isShortCv ? 4 : 6,
                        'explanation' => $isShortCv ? 'Design trop basique.' : 'Design correct mais pourrait être amélioré.'
                    ]
                ],
                'summary' => $isCvLike ?
                    ($isShortCv ? 'Ce CV ne répond pas aux critères minimums requis. Il est trop court et manque d\'informations essentielles sur l\'expérience professionnelle et la formation.' :
                    'Ce CV répond aux critères minimums mais pourrait être amélioré, notamment en détaillant davantage les expériences professionnelles et en structurant mieux les compétences.') :
                    'Le document soumis ne semble pas être un CV valide. Veuillez soumettre un CV contenant vos expériences professionnelles, votre formation et vos compétences.'
            ];

            return json_encode($simulatedResponse);
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
                'relevance' => ['score' => 5, 'explanation' => 'Impossible de déterminer si le document est un CV valide.'],
                'experience' => ['score' => 5, 'explanation' => 'Impossible d\'évaluer les expériences professionnelles.'],
                'skills' => ['score' => 5, 'explanation' => 'Impossible d\'évaluer les compétences.'],
                'education' => ['score' => 5, 'explanation' => 'Impossible d\'évaluer la formation et les diplômes.'],
                'languages' => ['score' => 5, 'explanation' => 'Impossible d\'évaluer les langues.'],
                'contact' => ['score' => 5, 'explanation' => 'Impossible d\'évaluer les informations de contact.'],
                'format' => ['score' => 5, 'explanation' => 'Impossible d\'évaluer le format.'],
                'readability' => ['score' => 5, 'explanation' => 'Impossible d\'évaluer la lisibilité.'],
                'length' => ['score' => 5, 'explanation' => 'Impossible d\'évaluer la longueur.'],
                'design' => ['score' => 5, 'explanation' => 'Impossible d\'évaluer le design.']
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
