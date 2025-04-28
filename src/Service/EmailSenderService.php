<?php

namespace App\Service;

use Mailjet\Client;

class EmailSenderService
{
    private Client $client;

    public function __construct(string $mailjetApiKey, string $mailjetApiSecret)
    {
        $this->client = new Client($mailjetApiKey, $mailjetApiSecret, true, ['version' => 'v3.1']);
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}
