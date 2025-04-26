<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\File\File;

class CvAnalyzerService
{
    private const AFFINDA_API_URL = 'https://api.affinda.com/v3/documents';
    private const AFFINDA_WORKSPACE_ID = 'WORKSPACE_ID_REMOVED';
    private const AFFINDA_COLLECTION_ID = 'COLLECTION_ID_REMOVED';

    private string $affindaApiKey;

    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->affindaApiKey = $_ENV['AFFINDA_API_KEY'] ?? 'AFFINDA_API_KEY_REMOVED';
    }

    public function analyzeCv(string $cvPath): array
    {
        $this->logger->info('Début de l\'analyse du CV: ' . $cvPath);

        try {
            // Vérifier si le fichier existe
            if (!file_exists($cvPath)) {
                $this->logger->error('Fichier CV non trouvé: ' . $cvPath);
                return [
                    'success' => false,
                    'message' => 'Fichier CV non trouvé: ' . $cvPath
                ];
            }

            $cvFile = new File($cvPath);

            // Vérifier la taille du fichier
            if ($cvFile->getSize() > 10 * 1024 * 1024) {
                $this->logger->error('Fichier CV trop volumineux: ' . $cvFile->getSize() . ' bytes');
                return [
                    'success' => false,
                    'message' => 'Le fichier CV est trop volumineux (max 10MB).'
                ];
            }

            // Vérifier l'extension du fichier
            $extension = strtolower($cvFile->getExtension());
            if (!in_array($extension, ['pdf', 'doc', 'docx'])) {
                $this->logger->error('Format de fichier non supporté: ' . $extension);
                return [
                    'success' => false,
                    'message' => 'Format de fichier non supporté. Utilisez PDF, DOC ou DOCX.'
                ];
            }

            // Si c'est un PDF, essayer d'abord l'extraction directe
            if ($extension === 'pdf') {
                $this->logger->info('Fichier PDF détecté, tentative d\'extraction directe du texte...');
                $pdfText = $this->extractTextFromPdf($cvPath);

                if (!empty($pdfText)) {
                    $this->logger->info('Texte extrait du PDF avec succès: ' . strlen($pdfText) . ' caractères');

                    // Créer un résultat avec le texte brut
                    $result = [
                        'summary' => '',
                        'skills' => [],
                        'experience' => [],
                        'education' => [],
                        'rawText' => $pdfText
                    ];

                    // Analyser le texte brut
                    $this->analyzeRawText($pdfText, $result);

                    // Si l'analyse a réussi et a trouvé des informations, retourner le résultat
                    if (!empty($result['skills']) || !empty($result['experience']) || !empty($result['education'])) {
                        $this->logger->info('Analyse locale réussie, informations extraites');
                        return [
                            'success' => true,
                            'data' => $result,
                            'source' => 'local_extraction'
                        ];
                    }

                    // Sinon, continuer avec l'API Affinda comme plan B
                    $this->logger->info('Analyse locale insuffisante, tentative avec l\'API Affinda...');
                }
            }

            // Préparer la requête à l'API Affinda
            $fileContent = file_get_contents($cvPath);
            $this->logger->info('Taille du fichier : ' . strlen($fileContent) . ' octets');

            // Créer un fichier temporaire pour l'upload multipart
            $tempFile = tempnam(sys_get_temp_dir(), 'cv_');
            file_put_contents($tempFile, $fileContent);

            // Créer les données du formulaire
            $formData = [
                'file' => curl_file_create($tempFile, 'application/pdf', $cvFile->getFilename()),
                'wait' => 'true',
                'workspace' => self::AFFINDA_WORKSPACE_ID,
                'collection' => self::AFFINDA_COLLECTION_ID
            ];

            $this->logger->info('Fichier temporaire créé : ' . $tempFile);

            $this->logger->info('Envoi de la requête à Affinda...');
            $this->logger->info('URL: ' . self::AFFINDA_API_URL);
            $this->logger->info('Collection ID: ' . self::AFFINDA_COLLECTION_ID);
            $this->logger->info('Workspace ID: ' . self::AFFINDA_WORKSPACE_ID);

            // Utiliser cURL directement pour un meilleur contrôle
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => self::AFFINDA_API_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $formData,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Authorization: Bearer ' . self::AFFINDA_API_KEY
                ],
            ]);

            $responseBody = curl_exec($curl);
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);

            curl_close($curl);

            // Supprimer le fichier temporaire
            if (file_exists($tempFile)) {
                unlink($tempFile);
                $this->logger->info('Fichier temporaire supprimé : ' . $tempFile);
            }

            if ($error) {
                $this->logger->error('Erreur cURL : ' . $error);

                // En cas d'erreur avec l'API, essayer d'extraire le texte directement du PDF
                $this->logger->info('Tentative d\'extraction directe du texte du PDF...');
                $pdfText = $this->extractTextFromPdf($cvPath);

                if (!empty($pdfText)) {
                    $this->logger->info('Texte extrait du PDF : ' . strlen($pdfText) . ' caractères');

                    // Créer un résultat avec le texte brut
                    $result = [
                        'summary' => '',
                        'skills' => [],
                        'experience' => [],
                        'education' => [],
                        'rawText' => $pdfText
                    ];

                    // Analyser le texte brut
                    $this->analyzeRawText($pdfText, $result);

                    return [
                        'success' => true,
                        'data' => $result,
                        'source' => 'local_extraction'
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'Erreur lors de la communication avec l\'API : ' . $error
                ];
            }

            $this->logger->info('Réponse de l\'API Affinda: Code ' . $statusCode);
            $this->logger->info('Réponse brute: ' . substr($responseBody, 0, 1000) . (strlen($responseBody) > 1000 ? '...' : ''));

            if ($statusCode === 200 || $statusCode === 201) {
                $data = json_decode($responseBody, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->logger->error('Erreur de décodage JSON: ' . json_last_error_msg());
                    return [
                        'success' => false,
                        'message' => 'Erreur de décodage de la réponse: ' . json_last_error_msg()
                    ];
                }

                $this->logger->info('Réponse décodée: ' . print_r($data, true));

                // Traiter les résultats
                $analysisResult = $this->processApiResponse($data);

                return [
                    'success' => true,
                    'data' => $analysisResult
                ];
            } else {
                $this->logger->error('Erreur lors de l\'analyse du CV: ' . $statusCode);
                $errorMessage = 'Erreur lors de l\'analyse du CV.';

                if ($statusCode === 401) {
                    $errorMessage .= ' Erreur d\'authentification. Veuillez vérifier la clé API.';
                } elseif ($statusCode === 404) {
                    $errorMessage .= ' Service non trouvé. Veuillez vérifier l\'URL de l\'API.';
                } else {
                    $errorMessage .= ' Code: ' . $statusCode . ' Réponse: ' . $responseBody;
                }

                // En cas d'erreur avec l'API, essayer d'extraire le texte directement du PDF
                $this->logger->info('Tentative d\'extraction directe du texte du PDF après erreur API...');
                $pdfText = $this->extractTextFromPdf($cvPath);

                if (!empty($pdfText)) {
                    $this->logger->info('Texte extrait du PDF : ' . strlen($pdfText) . ' caractères');

                    // Créer un résultat avec le texte brut
                    $result = [
                        'summary' => '',
                        'skills' => [],
                        'experience' => [],
                        'education' => [],
                        'rawText' => $pdfText
                    ];

                    // Analyser le texte brut
                    $this->analyzeRawText($pdfText, $result);

                    return [
                        'success' => true,
                        'data' => $result,
                        'source' => 'local_extraction'
                    ];
                }

                return [
                    'success' => false,
                    'message' => $errorMessage
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception lors de l\'analyse du CV: ' . $e->getMessage());

            // En cas d'exception, essayer d'extraire le texte directement du PDF
            try {
                $this->logger->info('Tentative d\'extraction directe du texte du PDF après exception...');
                $pdfText = $this->extractTextFromPdf($cvPath);

                if (!empty($pdfText)) {
                    $this->logger->info('Texte extrait du PDF : ' . strlen($pdfText) . ' caractères');

                    // Créer un résultat avec le texte brut
                    $result = [
                        'summary' => '',
                        'skills' => [],
                        'experience' => [],
                        'education' => [],
                        'rawText' => $pdfText
                    ];

                    // Analyser le texte brut
                    $this->analyzeRawText($pdfText, $result);

                    return [
                        'success' => true,
                        'data' => $result,
                        'source' => 'local_extraction'
                    ];
                }
            } catch (\Exception $innerException) {
                $this->logger->error('Exception lors de l\'extraction directe du texte: ' . $innerException->getMessage());
            }

            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'analyse du CV: ' . $e->getMessage()
            ];
        }
    }

    private function processApiResponse(array $data): array
    {
        $result = [
            'summary' => '',
            'skills' => [],
            'experience' => [],
            'education' => []
        ];

        $this->logger->info('Traitement de la réponse API: ' . json_encode($data, JSON_PRETTY_PRINT));

        // Vérifier si nous avons des données
        if (empty($data)) {
            $this->logger->warning('Aucune donnée dans la réponse API');
            return $result;
        }

        // Extraire les données selon la structure de la réponse
        $responseData = $data;
        if (isset($data['data'])) {
            $responseData = $data['data'];
        }

        // Résumé
        if (isset($responseData['summary']) && isset($responseData['summary']['parsed'])) {
            $result['summary'] = $responseData['summary']['parsed'];
            $this->logger->info('Résumé extrait: ' . substr($result['summary'], 0, 100) . '...');
        } else {
            $this->logger->warning('Aucun résumé trouvé dans la réponse');
        }

        // Compétences
        if (isset($responseData['skills']) && is_array($responseData['skills'])) {
            foreach ($responseData['skills'] as $skill) {
                if (isset($skill['name'])) {
                    $result['skills'][] = $skill['name'];
                }
            }
            $this->logger->info('Compétences extraites: ' . count($result['skills']));
        } else {
            $this->logger->warning('Aucune compétence trouvée dans la réponse');
        }

        // Expérience professionnelle
        if (isset($responseData['workExperience']) && is_array($responseData['workExperience'])) {
            foreach ($responseData['workExperience'] as $experience) {
                $jobTitle = $experience['jobTitle'] ?? 'Non spécifié';
                $organization = $experience['organization'] ?? 'Non spécifié';

                $startDate = null;
                $endDate = null;
                if (isset($experience['dates'])) {
                    $startDate = $experience['dates']['startDate'] ?? null;
                    $endDate = $experience['dates']['endDate'] ?? null;
                }

                $exp = [
                    'jobTitle' => $jobTitle,
                    'organization' => $organization,
                    'dates' => $this->formatDateRange($startDate, $endDate),
                    'description' => $experience['description'] ?? 'Non spécifié'
                ];
                $result['experience'][] = $exp;
            }
            $this->logger->info('Expériences extraites: ' . count($result['experience']));
        } else {
            $this->logger->warning('Aucune expérience trouvée dans la réponse');

            // Essayer d'extraire les expériences du texte brut
            if (isset($responseData['rawText'])) {
                $this->extractExperiencesFromRawText($responseData['rawText'], $result);
            }
        }

        // Formation
        if (isset($responseData['education']) && is_array($responseData['education'])) {
            foreach ($responseData['education'] as $education) {
                $institution = $education['organization'] ?? 'Non spécifié';
                $degree = 'Non spécifié';

                if (isset($education['accreditation']) && isset($education['accreditation']['education'])) {
                    $degree = $education['accreditation']['education'];
                }

                $startDate = null;
                $endDate = null;
                if (isset($education['dates'])) {
                    $startDate = $education['dates']['startDate'] ?? null;
                    $endDate = $education['dates']['endDate'] ?? null;
                }

                $edu = [
                    'institution' => $institution,
                    'degree' => $degree,
                    'dates' => $this->formatDateRange($startDate, $endDate)
                ];
                $result['education'][] = $edu;
            }
            $this->logger->info('Formations extraites: ' . count($result['education']));
        } else {
            $this->logger->warning('Aucune formation trouvée dans la réponse');

            // Essayer d'extraire les formations du texte brut
            if (isset($responseData['rawText'])) {
                $this->extractEducationFromRawText($responseData['rawText'], $result);
            }
        }

        // Texte brut pour analyse supplémentaire
        if (isset($responseData['rawText'])) {
            $result['rawText'] = $responseData['rawText'];
            $this->logger->info('Texte brut extrait: ' . strlen($responseData['rawText']) . ' caractères');

            // Analyse supplémentaire du texte brut si nécessaire
            $this->analyzeRawText($responseData['rawText'], $result);
        } else {
            $this->logger->warning('Aucun texte brut trouvé dans la réponse');
        }

        return $result;
    }

    private function formatDateRange($startDate, $endDate): string
    {
        $formattedStart = $startDate ? date('m/Y', strtotime($startDate)) : 'Non spécifié';
        $formattedEnd = $endDate ? date('m/Y', strtotime($endDate)) : 'Présent';

        return $formattedStart . ' - ' . $formattedEnd;
    }

    private function extractTextFromPdf(string $pdfPath): string
    {
        $this->logger->info('Extraction du texte du PDF: ' . $pdfPath);

        // Vérifier si le fichier existe
        if (!file_exists($pdfPath)) {
            $this->logger->error('Fichier PDF non trouvé: ' . $pdfPath);
            return '';
        }

        // Essayer d'abord avec la classe PHP pour PDF
        try {
            if (class_exists('\Smalot\PdfParser\Parser')) {
                $this->logger->info('Utilisation de PdfParser pour extraire le texte');
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($pdfPath);
                $text = $pdf->getText();

                if (!empty($text)) {
                    $this->logger->info('Texte extrait avec PdfParser: ' . strlen($text) . ' caractères');
                    return $text;
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Erreur avec PdfParser: ' . $e->getMessage());
        }

        // Essayer avec pdftotext si disponible
        try {
            // Vérifier si pdftotext est disponible
            $pdftotext = 'pdftotext';
            $command = "$pdftotext -q -enc UTF-8 '$pdfPath' -";

            $this->logger->info('Exécution de la commande: ' . $command);
            $output = shell_exec($command);

            if ($output !== null && !empty(trim($output))) {
                $this->logger->info('Texte extrait avec pdftotext: ' . strlen($output) . ' caractères');
                return $output;
            }
        } catch (\Exception $e) {
            $this->logger->warning('Erreur avec pdftotext: ' . $e->getMessage());
        }

        // Si les méthodes précédentes ont échoué, essayer la méthode alternative
        $this->logger->warning('Les méthodes principales ont échoué, essai avec la méthode alternative');
        return $this->extractTextFromPdfAlternative($pdfPath);
    }

    private function extractTextFromPdfAlternative(string $pdfPath): string
    {
        $this->logger->info('Extraction alternative du texte du PDF: ' . $pdfPath);

        // Méthode alternative utilisant PHP pour extraire le texte
        try {
            // Lire le fichier PDF en binaire
            $content = file_get_contents($pdfPath);

            // Recherche de texte dans le contenu binaire
            $text = '';

            // Recherche de blocs de texte entre parenthèses
            preg_match_all('/\((.*?)\)/s', $content, $matches);

            foreach ($matches[1] as $match) {
                // Filtrer les caractères non imprimables
                $filtered = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $match);
                if (strlen($filtered) > 3) { // Ignorer les chaînes trop courtes
                    $text .= $filtered . "\n";
                }
            }

            // Si aucun texte n'a été extrait, essayer une autre approche
            if (empty(trim($text))) {
                // Recherche de blocs de texte entre /BT et /ET (Begin Text et End Text)
                preg_match_all('/\/BT(.*?)\/ET/s', $content, $matches);

                foreach ($matches[1] as $match) {
                    // Extraire les chaînes de texte
                    preg_match_all('/\[(.*?)\]/s', $match, $textMatches);

                    foreach ($textMatches[1] as $textMatch) {
                        $filtered = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $textMatch);
                        if (strlen($filtered) > 3) {
                            $text .= $filtered . "\n";
                        }
                    }
                }
            }

            // Si toujours pas de texte, essayer une approche plus simple
            if (empty(trim($text))) {
                // Recherche de texte lisible dans le contenu
                $filtered = preg_replace('/[^a-zA-Z0-9\s\.,;:\-_\'"\(\)\[\]\{\}\+\*\/\\\?\!\@\#\$\%\^\&\=]/', ' ', $content);
                $filtered = preg_replace('/\s+/', ' ', $filtered);

                // Extraire des segments qui ressemblent à du texte (au moins 5 caractères alphanumériques consécutifs)
                preg_match_all('/[a-zA-Z0-9\s\.,;:\-_]{5,}/', $filtered, $textMatches);

                foreach ($textMatches[0] as $match) {
                    if (strlen($match) > 10) { // Ignorer les segments trop courts
                        $text .= $match . "\n";
                    }
                }
            }

            // Nettoyer le texte final
            $text = preg_replace('/\s+/', ' ', $text); // Remplacer les espaces multiples par un seul
            $text = preg_replace('/\n\s*\n/', "\n", $text); // Supprimer les lignes vides

            $this->logger->info('Texte extrait avec la méthode alternative: ' . strlen($text) . ' caractères');
            return $text;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'extraction alternative du texte: ' . $e->getMessage());
            return '';
        }
    }

    private function analyzeRawText(string $rawText, array &$result): void
    {
        $this->logger->info('Analyse du texte brut: ' . strlen($rawText) . ' caractères');

        // Analyse des compétences si elles n'ont pas été détectées automatiquement
        if (empty($result['skills'])) {
            $this->extractSkillsFromRawText($rawText, $result);
        }

        // Analyse de l'expérience professionnelle si elle n'a pas été détectée automatiquement
        if (empty($result['experience'])) {
            $this->extractExperiencesFromRawText($rawText, $result);
        }

        // Analyse de la formation si elle n'a pas été détectée automatiquement
        if (empty($result['education'])) {
            $this->extractEducationFromRawText($rawText, $result);
        }
    }

    private function extractSkillsFromRawText(string $rawText, array &$result): void
    {
        $this->logger->info('Extraction des compétences du texte brut');

        // Recherche de mots-clés courants pour les compétences
        $skillKeywords = [
            'COMPÉTENCES', 'COMPETENCES', 'SKILLS', 'TECHNOLOGIES', 'LANGAGES',
            'OUTILS', 'FRAMEWORKS', 'LANGUAGES', 'TECHNICAL SKILLS'
        ];

        $lines = explode("\n", $rawText);
        $inSkillsSection = false;
        $skillsFound = false;

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            // Détection du début de la section compétences
            foreach ($skillKeywords as $keyword) {
                if (stripos($trimmedLine, $keyword) !== false) {
                    $inSkillsSection = true;
                    $this->logger->info('Section de compétences trouvée avec le mot-clé: ' . $keyword);
                    break;
                }
            }

            // Extraction des compétences
            if ($inSkillsSection) {
                // Détection de la fin de la section
                if (empty($trimmedLine) || preg_match('/^(FORMATION|EDUCATION|EXPERIENCE|PARCOURS)/i', $trimmedLine)) {
                    $inSkillsSection = false;
                    continue;
                }

                // Extraction des compétences avec puces
                if (strpos($trimmedLine, '•') === 0 || strpos($trimmedLine, '-') === 0 || strpos($trimmedLine, '*') === 0) {
                    $skill = trim(preg_replace('/^[•\-\*]\s*/', '', $trimmedLine));
                    if (!empty($skill)) {
                        $result['skills'][] = $skill;
                        $skillsFound = true;
                    }
                }
                // Extraction des compétences séparées par des virgules
                elseif (strpos($trimmedLine, ',') !== false) {
                    $skills = array_map('trim', explode(',', $trimmedLine));
                    foreach ($skills as $skill) {
                        if (!empty($skill)) {
                            $result['skills'][] = $skill;
                            $skillsFound = true;
                        }
                    }
                }
                // Extraction des compétences individuelles
                elseif (strlen($trimmedLine) > 2 && strlen($trimmedLine) < 50 && !preg_match('/^(FORMATION|EDUCATION|EXPERIENCE|PARCOURS)/i', $trimmedLine)) {
                    $result['skills'][] = $trimmedLine;
                    $skillsFound = true;
                }
            }
        }

        if ($skillsFound) {
            $this->logger->info('Compétences extraites du texte brut: ' . count($result['skills']));
        } else {
            $this->logger->warning('Aucune compétence n\'a pu être extraite du texte brut');
        }
    }

    private function extractExperiencesFromRawText(string $rawText, array &$result): void
    {
        $this->logger->info('Extraction des expériences du texte brut');

        // Recherche de mots-clés courants pour les expériences
        $experienceKeywords = [
            'EXPERIENCE', 'EXPERIENCES', 'EXPERIENCES PROFESSIONNELLES', 'PARCOURS PROFESSIONNEL',
            'WORK EXPERIENCE', 'PROFESSIONAL EXPERIENCE', 'EMPLOIS', 'POSTES OCCUPÉS'
        ];

        $lines = explode("\n", $rawText);
        $inExperienceSection = false;
        $currentExperience = null;
        $experiencesFound = false;

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            // Détection du début de la section expériences
            foreach ($experienceKeywords as $keyword) {
                if (stripos($trimmedLine, $keyword) !== false) {
                    $inExperienceSection = true;
                    $this->logger->info('Section d\'expériences trouvée avec le mot-clé: ' . $keyword);
                    break;
                }
            }

            // Extraction des expériences
            if ($inExperienceSection) {
                // Détection de la fin de la section
                if (preg_match('/^(FORMATION|EDUCATION|COMP[EÉ]TENCES)/i', $trimmedLine)) {
                    $inExperienceSection = false;
                    continue;
                }

                // Détection d'une nouvelle expérience (date + titre)
                if (preg_match('/^(\d{4})\s*[-–]\s*(\d{4}|[Pp]r[eé]sent)/', $trimmedLine) ||
                    preg_match('/^([A-Z][a-z]+\s+\d{4})\s*[-–]\s*([A-Z][a-z]+\s+\d{4}|[Pp]r[eé]sent)/', $trimmedLine)) {

                    // Sauvegarder l'expérience précédente si elle existe
                    if ($currentExperience !== null) {
                        $result['experience'][] = $currentExperience;
                        $experiencesFound = true;
                    }

                    // Créer une nouvelle expérience
                    $currentExperience = [
                        'jobTitle' => 'Non spécifié',
                        'organization' => 'Non spécifié',
                        'dates' => $trimmedLine,
                        'description' => ''
                    ];

                    // Essayer d'extraire le titre du poste et l'organisation
                    $nextLine = isset($lines[array_search($line, $lines) + 1]) ? trim($lines[array_search($line, $lines) + 1]) : '';
                    if (!empty($nextLine) && !preg_match('/^\d{4}/', $nextLine)) {
                        $currentExperience['jobTitle'] = $nextLine;

                        // Essayer d'extraire l'organisation
                        $nextNextLine = isset($lines[array_search($line, $lines) + 2]) ? trim($lines[array_search($line, $lines) + 2]) : '';
                        if (!empty($nextNextLine) && !preg_match('/^\d{4}/', $nextNextLine)) {
                            $currentExperience['organization'] = $nextNextLine;
                        }
                    }
                }
                // Ajouter à la description de l'expérience courante
                elseif ($currentExperience !== null && !empty($trimmedLine) &&
                       !preg_match('/^(\d{4})\s*[-–]\s*(\d{4}|[Pp]r[eé]sent)/', $trimmedLine) &&
                       $trimmedLine !== $currentExperience['jobTitle'] &&
                       $trimmedLine !== $currentExperience['organization']) {

                    if (!empty($currentExperience['description'])) {
                        $currentExperience['description'] .= "\n";
                    }
                    $currentExperience['description'] .= $trimmedLine;
                }
            }
        }

        // Ajouter la dernière expérience si elle existe
        if ($currentExperience !== null) {
            $result['experience'][] = $currentExperience;
            $experiencesFound = true;
        }

        if ($experiencesFound) {
            $this->logger->info('Expériences extraites du texte brut: ' . count($result['experience']));
        } else {
            $this->logger->warning('Aucune expérience n\'a pu être extraite du texte brut');
        }
    }

    private function extractEducationFromRawText(string $rawText, array &$result): void
    {
        $this->logger->info('Extraction des formations du texte brut');

        // Recherche de mots-clés courants pour les formations
        $educationKeywords = [
            'FORMATION', 'FORMATIONS', 'EDUCATION', 'ÉDUCATION', 'DIPLOMES', 'DIPLÔMES',
            'CURSUS', 'STUDIES', 'ACADEMIC BACKGROUND'
        ];

        $lines = explode("\n", $rawText);
        $inEducationSection = false;
        $currentEducation = null;
        $educationsFound = false;

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            // Détection du début de la section formations
            foreach ($educationKeywords as $keyword) {
                if (stripos($trimmedLine, $keyword) !== false) {
                    $inEducationSection = true;
                    $this->logger->info('Section de formations trouvée avec le mot-clé: ' . $keyword);
                    break;
                }
            }

            // Extraction des formations
            if ($inEducationSection) {
                // Détection de la fin de la section
                if (preg_match('/^(EXPERIENCE|COMP[EÉ]TENCES|LANGUES)/i', $trimmedLine)) {
                    $inEducationSection = false;
                    continue;
                }

                // Détection d'une nouvelle formation (date + diplôme)
                if (preg_match('/^(\d{4})\s*[-–]\s*(\d{4}|[Pp]r[eé]sent)/', $trimmedLine) ||
                    preg_match('/^([A-Z][a-z]+\s+\d{4})\s*[-–]\s*([A-Z][a-z]+\s+\d{4}|[Pp]r[eé]sent)/', $trimmedLine)) {

                    // Sauvegarder la formation précédente si elle existe
                    if ($currentEducation !== null) {
                        $result['education'][] = $currentEducation;
                        $educationsFound = true;
                    }

                    // Créer une nouvelle formation
                    $currentEducation = [
                        'degree' => 'Non spécifié',
                        'institution' => 'Non spécifié',
                        'dates' => $trimmedLine
                    ];

                    // Essayer d'extraire le diplôme et l'institution
                    $nextLine = isset($lines[array_search($line, $lines) + 1]) ? trim($lines[array_search($line, $lines) + 1]) : '';
                    if (!empty($nextLine) && !preg_match('/^\d{4}/', $nextLine)) {
                        $currentEducation['degree'] = $nextLine;

                        // Essayer d'extraire l'institution
                        $nextNextLine = isset($lines[array_search($line, $lines) + 2]) ? trim($lines[array_search($line, $lines) + 2]) : '';
                        if (!empty($nextNextLine) && !preg_match('/^\d{4}/', $nextNextLine)) {
                            $currentEducation['institution'] = $nextNextLine;
                        }
                    }
                }
                // Détection d'un diplôme sans date
                elseif (preg_match('/^(Dipl[oô]me|Master|Licence|Baccalaur[eé]at|BTS|DUT|Doctorat|MBA|Ing[eé]nieur)/i', $trimmedLine)) {
                    // Sauvegarder la formation précédente si elle existe
                    if ($currentEducation !== null) {
                        $result['education'][] = $currentEducation;
                        $educationsFound = true;
                    }

                    // Créer une nouvelle formation
                    $currentEducation = [
                        'degree' => $trimmedLine,
                        'institution' => 'Non spécifié',
                        'dates' => 'Non spécifié'
                    ];

                    // Essayer d'extraire l'institution
                    $nextLine = isset($lines[array_search($line, $lines) + 1]) ? trim($lines[array_search($line, $lines) + 1]) : '';
                    if (!empty($nextLine) && !preg_match('/^(Dipl[oô]me|Master|Licence|Baccalaur[eé]at|BTS|DUT|Doctorat|MBA|Ing[eé]nieur)/i', $nextLine)) {
                        $currentEducation['institution'] = $nextLine;
                    }
                }
            }
        }

        // Ajouter la dernière formation si elle existe
        if ($currentEducation !== null) {
            $result['education'][] = $currentEducation;
            $educationsFound = true;
        }

        if ($educationsFound) {
            $this->logger->info('Formations extraites du texte brut: ' . count($result['education']));
        } else {
            $this->logger->warning('Aucune formation n\'a pu être extraite du texte brut');
        }
    }
}
