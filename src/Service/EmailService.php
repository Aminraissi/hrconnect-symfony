<?php

namespace App\Service;

use App\Entity\Candidature;
use App\Entity\OffreEmploi;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class EmailService
{
    private const MAILJET_API_KEY_PUBLIC = '9cc981b250baf8bf77bc099729f2b4a9';
    private const MAILJET_API_KEY_PRIVATE = 'ac6ae15664559f441a33bb0a379e905b';
    private const MAILJET_TEMPLATE_ID_ACCEPTED = '6766487';
    private const MAILJET_TEMPLATE_ID_REJECTED = '6766480';

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function sendEmail(Candidature $candidature, string $status): bool
    {
        try {
            $this->logger->info('=== DÉBUT DE L\'ENVOI D\'EMAIL ===');
            $this->logger->info('Status: ' . $status);

            $candidat = $candidature;
            $offreEmploi = $candidature->getOffre();

            if ($candidat === null || $offreEmploi === null) {
                throw new \Exception('Données candidat ou offre manquantes');
            }

            $templateId = $status === 'accepted' ? self::MAILJET_TEMPLATE_ID_ACCEPTED : self::MAILJET_TEMPLATE_ID_REJECTED;
            $this->logger->info('Template ID utilisé: ' . $templateId);

            // Test de l'authentification
            $auth = self::MAILJET_API_KEY_PUBLIC . ':' . self::MAILJET_API_KEY_PRIVATE;
            $encodedAuth = base64_encode($auth);
            $this->logger->info('Clés utilisées - Public: ' . substr(self::MAILJET_API_KEY_PUBLIC, 0, 5) . '..., Private: ' . substr(self::MAILJET_API_KEY_PRIVATE, 0, 5) . '...');

            $jsonPayload = [
                'Messages' => [
                    [
                        'From' => [
                            'Email' => 'aminraissi43@gmail.com',
                            'Name' => 'Service Recrutement'
                        ],
                        'To' => [
                            [
                                'Email' => $candidat->getEmail(),
                                'Name' => $candidat->getNom() . ' ' . $candidat->getPrenom()
                            ]
                        ],
                        'TemplateID' => (int)$templateId,
                        'TemplateLanguage' => true,
                        'Subject' => $status === 'accepted'
                            ? 'Félicitations ! Votre candidature a été acceptée'
                            : 'Mise à jour sur votre candidature',
                        'Variables' => [
                            'firstName' => $candidat->getPrenom(),
                            'lastName' => $candidat->getNom(),
                            'jobTitle' => $offreEmploi->getTitre()
                        ]
                    ]
                ]
            ];

            // Log du payload pour débogage
            $this->logger->info('Payload JSON: ' . json_encode($jsonPayload));

            $client = HttpClient::create();
            $response = $client->request('POST', 'https://api.mailjet.com/v3.1/send', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . $encodedAuth
                ],
                'json' => $jsonPayload,
                'timeout' => 30
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);

            // Log de la réponse complète pour débogage

            $this->logger->info('Code de réponse: ' . $statusCode);
            $this->logger->info('Réponse complète: ' . $content);

            if ($statusCode === 200 || $statusCode === 201) {
                $this->logger->info('Email envoyé avec succès!');
                return true;
            } else {
                throw new \Exception('Échec de l\'envoi de l\'email. Code: ' . $statusCode . ', Réponse: ' . $content);
            }
        } catch (\Exception | TransportExceptionInterface $e) {
            $this->logger->error('ERREUR lors de l\'envoi de l\'email: ' . $e->getMessage());
            return false;
        } finally {
            $this->logger->info('=== FIN DE L\'ENVOI D\'EMAIL ===');
        }
    }
}
