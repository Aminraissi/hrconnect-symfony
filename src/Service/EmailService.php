<?php

namespace App\Service;

use App\Entity\Candidature;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class EmailService
{
    private const MAILJET_TEMPLATE_ID_ACCEPTED = '6766487';
    private const MAILJET_TEMPLATE_ID_REJECTED = '6766480';

    private LoggerInterface $logger;
    private MailerInterface $mailer;

    public function __construct(LoggerInterface $logger, MailerInterface $mailer)
    {
        $this->logger = $logger;
        $this->mailer = $mailer;
    }

    public function sendEmail(Candidature $candidature, string $status): bool
    {
        try {
            $this->logger->info('=== DÉBUT DE L\'ENVOI D\'EMAIL ===');
            $this->logger->info('Status: ' . $status);

            $candidat = $candidature->getCandidat();
            $offreEmploi = $candidature->getOffreEmploi();

            if ($candidat === null || $offreEmploi === null) {
                throw new \Exception('Données candidat ou offre manquantes');
            }

            // Préparer le contenu de l'email en fonction du statut
            $subject = $status === 'accepted'
                ? 'Félicitations ! Votre candidature a été acceptée'
                : 'Mise à jour sur votre candidature';

            $htmlContent = $this->getEmailTemplate($status, [
                'firstName' => $candidat->getFirstName(),
                'lastName' => $candidat->getLastName(),
                'jobTitle' => $offreEmploi->getTitle()
            ]);

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
            return false;
        } finally {
            $this->logger->info('=== FIN DE L\'ENVOI D\'EMAIL ===');
        }
    }

    private function getEmailTemplate(string $status, array $variables): string
    {
        // Template pour les candidatures acceptées
        if ($status === 'accepted') {
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
        // Template pour les candidatures refusées
        else {
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
    }
}
