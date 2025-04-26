<?php

namespace App\Service;

use App\Entity\Candidature;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class EmailService
{
    private string $mailjetTemplateIdAccepted;
    private string $mailjetTemplateIdRejected;
    private LoggerInterface $logger;
    private MailerInterface $mailer;

    public function __construct(LoggerInterface $logger, MailerInterface $mailer)
    {
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->mailjetTemplateIdAccepted = $_ENV['MAILJET_TEMPLATE_ID_ACCEPTED'] ?? '6766487';
        $this->mailjetTemplateIdRejected = $_ENV['MAILJET_TEMPLATE_ID_REJECTED'] ?? '6766480';
    }

    public function sendEmail(Candidature $candidature, string $status): bool
    {
        try {
            $this->logger->info('=== DÉBUT DE L\'ENVOI D\'EMAIL ===');
            $this->logger->info('Status reçu par sendEmail: "' . $status . '"');
            $this->logger->info('Candidature ID: ' . $candidature->getId());
            $this->logger->info('Candidature status dans la base: "' . $candidature->getStatus() . '"');

            $candidat = $candidature->getCandidat();
            $offreEmploi = $candidature->getOffreEmploi();

            if ($candidat === null || $offreEmploi === null) {
                throw new \Exception('Données candidat ou offre manquantes');
            }

            $this->logger->info('Candidat: ' . $candidat->getFirstName() . ' ' . $candidat->getLastName() . ' (' . $candidat->getEmail() . ')');
            $this->logger->info('Offre: ' . $offreEmploi->getTitle());

            // Déterminer le template à utiliser en fonction du statut
            // Utiliser le statut de la candidature si aucun statut n'est spécifié
            $actualStatus = $status ?: $candidature->getStatus();
            $this->logger->info('Statut utilisé pour l\'email: "' . $actualStatus . '"');

            if ($actualStatus === 'accepted' || $actualStatus === 'acceptee') {
                $this->logger->info('Envoi d\'un email d\'ACCEPTATION');
                $subject = 'Félicitations ! Votre candidature a été acceptée';
                $htmlContent = $this->getAcceptedTemplate([
                    'firstName' => $candidat->getFirstName(),
                    'lastName' => $candidat->getLastName(),
                    'jobTitle' => $offreEmploi->getTitle()
                ]);
            } else if ($actualStatus === 'rejected' || $actualStatus === 'refusee') {
                $this->logger->info('Envoi d\'un email de REFUS');
                $subject = 'Mise à jour sur votre candidature';
                $htmlContent = $this->getRejectedTemplate([
                    'firstName' => $candidat->getFirstName(),
                    'lastName' => $candidat->getLastName(),
                    'jobTitle' => $offreEmploi->getTitle()
                ]);
            } else {
                $this->logger->warning('Statut inconnu: "' . $actualStatus . '". Utilisation du template par défaut.');
                $subject = 'Mise à jour de votre candidature';
                $htmlContent = $this->getDefaultTemplate([
                    'firstName' => $candidat->getFirstName(),
                    'lastName' => $candidat->getLastName(),
                    'jobTitle' => $offreEmploi->getTitle()
                ]);
            }

            // Créer l'email
            $email = (new Email())
                ->from(new Address('aminraissi43@gmail.com', 'Service Recrutement'))
                ->to(new Address($candidat->getEmail(), $candidat->getLastName() . ' ' . $candidat->getFirstName()))
                ->subject($subject)
                ->html($htmlContent);

            // Envoyer l'email
            $this->mailer->send($email);

            $this->logger->info('Email envoyé avec succès à ' . $candidat->getEmail());
            return true;
        } catch (\Exception $e) {
            $this->logger->error('ERREUR lors de l\'envoi de l\'email: ' . $e->getMessage());
            $this->logger->error('Trace: ' . $e->getTraceAsString());
            return false;
        } finally {
            $this->logger->info('=== FIN DE L\'ENVOI D\'EMAIL ===');
        }
    }

    /**
     * Template pour les candidatures acceptées
     */
    private function getAcceptedTemplate(array $variables): string
    {
        $this->logger->info('Génération du template pour candidature acceptée');
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h1 style="color: #28a745;">Félicitations !</h1>
            </div>
            <p>Bonjour ' . $variables['firstName'] . ' ' . $variables['lastName'] . ',</p>
            <p>Nous sommes ravis de vous informer que votre candidature pour le poste de <strong>' . $variables['jobTitle'] . '</strong> a été acceptée !</p>
            <p>Notre équipe des ressources humaines vous contactera prochainement pour discuter des prochaines étapes du processus de recrutement.</p>
            <p>Nous sommes impatients de vous accueillir dans notre équipe.</p>
            <div style="margin-top: 30px;">
                <p>Cordialement,</p>
                <p><strong>L\'équipe de recrutement</strong></p>
            </div>
        </div>';
    }

    /**
     * Template pour les candidatures refusées
     */
    private function getRejectedTemplate(array $variables): string
    {
        $this->logger->info('Génération du template pour candidature refusée');
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h1 style="color: #dc3545;">Mise à jour de votre candidature</h1>
            </div>
            <p>Bonjour ' . $variables['firstName'] . ' ' . $variables['lastName'] . ',</p>
            <p>Nous vous remercions de l\'intérêt que vous avez porté à notre entreprise et pour votre candidature au poste de <strong>' . $variables['jobTitle'] . '</strong>.</p>
            <p>Après un examen attentif de votre profil, nous regrettons de vous informer que nous avons décidé de poursuivre avec d\'autres candidats dont les qualifications correspondent davantage aux exigences spécifiques de ce poste.</p>
            <p>Nous vous encourageons à consulter régulièrement notre site pour d\'autres opportunités qui pourraient mieux correspondre à votre profil.</p>
            <div style="margin-top: 30px;">
                <p>Cordialement,</p>
                <p><strong>L\'équipe de recrutement</strong></p>
            </div>
        </div>';
    }

    /**
     * Template par défaut en cas de statut inconnu
     */
    private function getDefaultTemplate(array $variables): string
    {
        $this->logger->info('Génération du template par défaut');
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h1 style="color: #007bff;">Mise à jour de votre candidature</h1>
            </div>
            <p>Bonjour ' . $variables['firstName'] . ' ' . $variables['lastName'] . ',</p>
            <p>Nous vous informons que le statut de votre candidature pour le poste de <strong>' . $variables['jobTitle'] . '</strong> a été mis à jour.</p>
            <p>Veuillez consulter votre espace candidat pour plus d\'informations.</p>
            <div style="margin-top: 30px;">
                <p>Cordialement,</p>
                <p><strong>L\'équipe de recrutement</strong></p>
            </div>
        </div>';
    }
}
