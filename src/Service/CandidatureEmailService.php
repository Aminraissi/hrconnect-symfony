<?php

namespace App\Service;

use App\Entity\Candidature;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class CandidatureEmailService
{
    private LoggerInterface $logger;
    private MailerInterface $mailer;

    public function __construct(LoggerInterface $logger, MailerInterface $mailer)
    {
        $this->logger = $logger;
        $this->mailer = $mailer;
    }

    /**
     * Envoie un email de confirmation de candidature avec la référence
     *
     * @param Candidature $candidature La candidature
     * @param bool|null $isAccepted true si acceptée, false si rejetée, null si erreur d'analyse
     * @return bool
     */
    public function sendConfirmationEmail(Candidature $candidature, ?bool $isAccepted = true): bool
    {
        try {
            $this->logger->info('=== DÉBUT DE L\'ENVOI D\'EMAIL DE CONFIRMATION ===');

            $candidat = $candidature->getCandidat();
            $offreEmploi = $candidature->getOffreEmploi();

            if ($candidat === null || $offreEmploi === null) {
                throw new \Exception('Données candidat ou offre manquantes');
            }

            // Préparer le contenu de l'email en fonction du statut
            if ($isAccepted === null) {
                $subject = 'Confirmation de votre candidature - Référence: ' . $candidature->getReference() . ' (Erreur d\'analyse)';
            } else {
                $subject = $isAccepted
                    ? 'Confirmation de votre candidature - Référence: ' . $candidature->getReference()
                    : 'Candidature non retenue - Score CV insuffisant';
            }

            $htmlContent = $this->getEmailTemplate($isAccepted, [
                'firstName' => $candidat->getFirstName(),
                'lastName' => $candidat->getLastName(),
                'jobTitle' => $offreEmploi->getTitle(),
                'reference' => $candidature->getReference()
            ]);

            // Créer l'email
            $email = (new Email())
                ->from(new Address('aminraissi43@gmail.com', 'Service Recrutement'))
                ->to(new Address($candidat->getEmail(), $candidat->getLastName() . ' ' . $candidat->getFirstName()))
                ->subject($subject)
                ->html($htmlContent);

            // Envoyer l'email
            $this->mailer->send($email);

            $this->logger->info('Email de confirmation envoyé avec succès à ' . $candidat->getEmail());
            return true;
        } catch (\Exception $e) {
            $this->logger->error('ERREUR lors de l\'envoi de l\'email de confirmation: ' . $e->getMessage());
            return false;
        } finally {
            $this->logger->info('=== FIN DE L\'ENVOI D\'EMAIL DE CONFIRMATION ===');
        }
    }

    /**
     * Génère le template d'email en fonction du statut
     *
     * @param bool|null $isAccepted true si acceptée, false si rejetée, null si erreur d'analyse
     * @param array $variables Les variables pour le template
     * @return string
     */
    private function getEmailTemplate(?bool $isAccepted, array $variables): string
    {
        // Template pour les candidatures acceptées (score CV >= 50%)
        if ($isAccepted === true) {
            return '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <h1 style="color: #28a745;">Candidature enregistrée</h1>
                </div>
                <p>Bonjour ' . $variables['firstName'] . ' ' . $variables['lastName'] . ',</p>
                <p>Nous vous confirmons que votre candidature pour le poste de <strong>' . $variables['jobTitle'] . '</strong> a bien été enregistrée dans notre système.</p>
                <p><strong>Votre référence de candidature est : ' . $variables['reference'] . '</strong></p>
                <p>Veuillez conserver cette référence précieusement. Elle vous permettra de suivre l\'état de votre candidature en utilisant la fonction "Suivre ma candidature" sur notre site.</p>
                <p>Votre CV a été analysé et répond aux critères minimaux requis pour ce poste. Votre candidature sera examinée par notre équipe de recrutement dans les meilleurs délais.</p>
                <p>Nous vous remercions de l\'intérêt que vous portez à notre entreprise.</p>
                <div style="margin-top: 30px;">
                    <p>Cordialement,</p>
                    <p><strong>L\'équipe de recrutement</strong></p>
                </div>
            </div>';
        }
        // Template pour les candidatures rejetées automatiquement (score CV < 50%)
        else if ($isAccepted === false) {
            return '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <h1 style="color: #dc3545;">Candidature non retenue</h1>
                </div>
                <p>Bonjour ' . $variables['firstName'] . ' ' . $variables['lastName'] . ',</p>
                <p>Nous vous remercions de l\'intérêt que vous avez porté à notre entreprise et pour votre candidature au poste de <strong>' . $variables['jobTitle'] . '</strong>.</p>
                <p>Après analyse automatique de votre CV, nous regrettons de vous informer que votre candidature n\'a pas été retenue car elle ne répond pas aux critères minimaux requis pour ce poste.</p>
                <p>Voici quelques conseils pour améliorer votre CV :</p>
                <ul>
                    <li>Assurez-vous que votre CV est au format PDF</li>
                    <li>Mettez en avant vos compétences et expériences pertinentes pour le poste</li>
                    <li>Veillez à ce que votre CV soit bien structuré et facile à lire</li>
                    <li>Incluez des informations sur vos diplômes et certifications</li>
                </ul>
                <p>Nous vous encourageons à consulter régulièrement notre site pour d\'autres opportunités qui pourraient mieux correspondre à votre profil.</p>
                <div style="margin-top: 30px;">
                    <p>Cordialement,</p>
                    <p><strong>L\'équipe de recrutement</strong></p>
                </div>
            </div>';
        }
        // Template pour les erreurs d'analyse de CV
        else {
            return '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <h1 style="color: #ffc107;">Candidature enregistrée</h1>
                </div>
                <p>Bonjour ' . $variables['firstName'] . ' ' . $variables['lastName'] . ',</p>
                <p>Nous vous confirmons que votre candidature pour le poste de <strong>' . $variables['jobTitle'] . '</strong> a bien été enregistrée dans notre système.</p>
                <p><strong>Votre référence de candidature est : ' . $variables['reference'] . '</strong></p>
                <p>Veuillez conserver cette référence précieusement. Elle vous permettra de suivre l\'\u00e9tat de votre candidature en utilisant la fonction "Suivre ma candidature" sur notre site.</p>
                <p>Nous avons rencontré une difficulté technique lors de l\'analyse de votre CV. Votre candidature a néanmoins été enregistrée et sera examinée manuellement par notre équipe de recrutement.</p>
                <p>Nous vous remercions de l\'intérêt que vous portez à notre entreprise.</p>
                <div style="margin-top: 30px;">
                    <p>Cordialement,</p>
                    <p><strong>L\'\u00e9quipe de recrutement</strong></p>
                </div>
            </div>';
        }
    }
}
