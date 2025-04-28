<?php

namespace App\Service;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;

class GmailService
{
    private string $credentialsPath;
    private string $tokenPath;

    public function __construct()
    {
        $this->credentialsPath = __DIR__ . '/../../config/google/credentials.json';
        $this->tokenPath = __DIR__ . '/../../config/google/token.json';
    }

    private function getClient(): Client
    {
        $client = new Client();
        $client->setApplicationName('Symfony Gmail API');
        $client->setScopes(Gmail::MAIL_GOOGLE_COM);
        $client->setAuthConfig($this->credentialsPath);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        if (file_exists($this->tokenPath)) {
            $accessToken = json_decode(file_get_contents($this->tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        if ($client->isAccessTokenExpired()) {
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            file_put_contents($this->tokenPath, json_encode($accessToken));
        }

        return $client;
    }

    public function sendEmail(string $to, string $subject, string $body): void
    {
        $client = $this->getClient();
        $service = new Gmail($client);

        $message = new Message();
        $rawMessage = sprintf(
            "To: %s\r\nSubject: %s\r\n\r\n%s",
            $to,
            $subject,
            $body
        );

        $encodedMessage = base64_encode($rawMessage);
        $encodedMessage = str_replace(['+', '/', '='], ['-', '_', ''], $encodedMessage); // URL-safe
        $message->setRaw($encodedMessage);

        $service->users_messages->send('me', $message);
    }
}
