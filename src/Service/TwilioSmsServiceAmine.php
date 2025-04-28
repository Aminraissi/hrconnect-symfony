<?php
namespace App\Service;

use Twilio\Rest\Client;
use Psr\Log\LoggerInterface;

class TwilioSmsServiceAmine
{
    private $twilio;
    private $from;
    private $logger;

    public function __construct(string $sid, string $token, string $from, LoggerInterface $logger)
    {
        $this->twilio = new Client($sid, $token);
        $this->from = $from;
        $this->logger = $logger;

        $this->logger->info('Service TwilioSmsServiceAmine initialisé');
        $this->logger->info('Numéro d\'expéditeur: ' . $from);
    }


    /**
     * Envoie un SMS à un numéro de téléphone
     *
     * Note: Dans un compte d'essai Twilio, vous ne pouvez envoyer des SMS qu'à des numéros vérifiés.
     * Pour les tests, nous utilisons un numéro de test américain valide.
     *
     * @param string $to Le numéro de téléphone du destinataire
     * @param string $message Le contenu du message
     * @return string Le SID du message envoyé ou un message d'erreur
     */
    public function sendSms(string $to, string $message): string
    {
        // Formater le numéro de téléphone (ajouter +216 si nécessaire)
        $formattedNumber = $this->formatPhoneNumber($to);

        $this->logger->info('Numéro original: ' . $to);
        $this->logger->info('Numéro formaté: ' . $formattedNumber);
        $this->logger->info('Message: ' . $message);

        // Toujours enregistrer le SMS dans un fichier de log
        $logFile = __DIR__ . '/../../var/log/sms_log.txt';
        $logContent = date('Y-m-d H:i:s') . ' - Numéro original: ' . $to . ' - Numéro formaté: ' . $formattedNumber . ' - Message: ' . $message . PHP_EOL;
        file_put_contents($logFile, $logContent, FILE_APPEND);

        // TOUJOURS envoyer le SMS au numéro vérifié +21696200228, quel que soit le numéro inséré
        $verifiedNumber = '+21696200228';
        $this->logger->info('Redirection du SMS vers le numéro vérifié: ' . $verifiedNumber);

        try {
            // Envoyer le SMS au numéro vérifié
            $response = $this->twilio->messages->create(
                $verifiedNumber, // Toujours utiliser le numéro vérifié
                [
                    'from' => $this->from,
                    'body' => $message
                ]
            );

            $this->logger->info('SMS envoyé avec succès au numéro vérifié! SID: ' . $response->sid);
            return $response->sid;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi du SMS: ' . $e->getMessage());
            $this->logger->error('Trace: ' . $e->getTraceAsString());

            // Enregistrer l'erreur dans un fichier de log
            $errorLogFile = __DIR__ . '/../../var/log/sms_error_log.txt';
            $errorLogContent = date('Y-m-d H:i:s') . ' - Erreur: ' . $e->getMessage() . ' - Numéro original: ' . $to . ' - Numéro formaté: ' . $formattedNumber . ' - Message: ' . $message . PHP_EOL;
            file_put_contents($errorLogFile, $errorLogContent, FILE_APPEND);

            // Même en cas d'erreur, retourner un ID simulé pour ne pas bloquer le processus
            return 'ERROR_SID_' . uniqid();
        }
    }

    /**
     * Envoie un SMS de confirmation de candidature
     * Cette méthode est garantie de ne jamais échouer et de toujours retourner un ID
     * En cas d'erreur, un message est enregistré dans les logs
     *
     * @param string $to Le numéro de téléphone du destinataire
     * @param string $firstName Le prénom du candidat
     * @param string $jobTitle Le titre du poste
     * @param string $reference La référence de candidature
     * @param bool $isAccepted true si acceptée, false si rejetée
     * @return string Le SID du message envoyé ou un ID simulé
     */
    public function sendCandidatureConfirmation(string $to, string $firstName, string $jobTitle, string $reference, bool $isAccepted = true): string
    {
        try {
            $this->logger->info('Préparation du SMS de candidature pour: ' . $firstName);
            $this->logger->info('Poste: ' . $jobTitle . ', Référence: ' . $reference . ', Accepté: ' . ($isAccepted ? 'Oui' : 'Non'));

            // Vérifier si le numéro de téléphone est valide
            if (empty($to)) {
                $this->logger->warning('Numéro de téléphone vide ou invalide');
                return 'SIMULATED_SID_EMPTY_PHONE_' . uniqid();
            }

            // Générer le message
            $message = $this->getCandidatureMessage($firstName, $jobTitle, $reference, $isAccepted);

            // Envoyer le SMS (le numéro sera formaté dans la méthode sendSms)
            return $this->sendSms($to, $message);
        } catch (\Exception $e) {
            // En cas d'erreur inattendue, enregistrer l'erreur et retourner un ID simulé
            $this->logger->error('Exception lors de l\'envoi du SMS de candidature: ' . $e->getMessage());
            return 'SIMULATED_SID_EXCEPTION_' . uniqid();
        }
    }

    /**
     * Formate le numéro de téléphone au format international
     *
     * @param string $phoneNumber Le numéro de téléphone à formater
     * @return string Le numéro formaté
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Si le numéro est vide, retourner une chaîne vide
        if (empty($phoneNumber)) {
            return '';
        }

        // Supprimer tous les caractères non numériques sauf le +
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

        // Si le numéro commence déjà par +216, le laisser tel quel
        if (substr($phoneNumber, 0, 4) === '+216') {
            return $phoneNumber;
        }

        // Si le numéro commence par 216 sans +, ajouter le +
        if (substr($phoneNumber, 0, 3) === '216') {
            return '+' . $phoneNumber;
        }

        // Si le numéro commence par 0, le remplacer par +216
        if (substr($phoneNumber, 0, 1) === '0') {
            return '+216' . substr($phoneNumber, 1);
        }

        // Pour tous les autres cas, ajouter +216 (tous les numéros sont tunisiens)
        return '+216' . $phoneNumber;
    }

    /**
     * Génère le message pour une candidature
     *
     * @param string $firstName Le prénom du candidat
     * @param string $jobTitle Le titre du poste
     * @param string $reference La référence de candidature
     * @param bool $isAccepted true si acceptée, false si rejetée
     * @return string Le message formaté
     */
    private function getCandidatureMessage(string $firstName, string $jobTitle, string $reference, bool $isAccepted): string
    {
        if ($isAccepted) {
            return "Bonjour {$firstName}, félicitations ! Votre candidature pour le poste de {$jobTitle} a été acceptée. Nous vous contacterons prochainement pour les prochaines étapes. L'équipe de recrutement.";
        } else if ($reference) {
            // Message pour une nouvelle candidature (avec référence)
            return "Bonjour {$firstName}, votre candidature pour le poste de {$jobTitle} a été enregistrée. Votre référence est : {$reference}. Conservez-la pour suivre votre candidature. Vous pouvez consulter l'état de votre candidature sur notre site. L'équipe de recrutement.";
        } else {
            // Message pour une candidature rejetée
            return "Bonjour {$firstName}, nous avons bien reçu votre candidature pour le poste de {$jobTitle}. Malheureusement, votre candidature n'a pas été retenue. Nous vous remercions de l'intérêt que vous portez à notre entreprise. L'équipe de recrutement.";
        }
    }
}
