<?php

// Clé API Gemini
$apiKey = 'AIzaSyDJC5a882ruaJN2HQR9nz_Rk_7kHHJgc-Y';

// URL de l'API
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey;

// Données à envoyer
$data = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => 'Explain how AI works in one sentence'
                ]
            ]
        ]
    ]
];

// Initialiser cURL
$ch = curl_init($url);

// Configurer la requête
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

// Exécuter la requête
$response = curl_exec($ch);

// Vérifier les erreurs
if (curl_errno($ch)) {
    echo 'Erreur cURL: ' . curl_error($ch) . "\n";
} else {
    // Afficher la réponse
    echo "Réponse de l'API Gemini:\n";
    $responseData = json_decode($response, true);
    echo json_encode($responseData, JSON_PRETTY_PRINT);
}

// Fermer la session cURL
curl_close($ch);
