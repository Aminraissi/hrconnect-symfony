<?php

// Ce script est destiné à être exécuté en ligne de commande pour tester le service EmailService

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Créer le kernel
$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

// Récupérer le container
$container = $kernel->getContainer();

// Récupérer le service EmailService
$emailService = $container->get('App\Service\EmailService');

// Récupérer le logger
$logger = $container->get('logger');

// Récupérer une candidature
$candidatureRepository = $container->get('doctrine')->getRepository(\App\Entity\Candidature::class);
$candidature = $candidatureRepository->findOneBy([], ['id' => 'DESC']);

if (!$candidature) {
    echo "Aucune candidature trouvée.\n";
    exit(1);
}

// Tester l'envoi d'email avec le statut 'accepted'
echo "Test d'envoi d'email avec le statut 'accepted'...\n";
$result = $emailService->sendEmail($candidature, 'accepted');
echo "Résultat: " . ($result ? "Succès" : "Échec") . "\n";

// Tester l'envoi d'email avec le statut 'rejected'
echo "Test d'envoi d'email avec le statut 'rejected'...\n";
$result = $emailService->sendEmail($candidature, 'rejected');
echo "Résultat: " . ($result ? "Succès" : "Échec") . "\n";

echo "Tests terminés.\n";
