<?php
// src/Service/OcrSpaceService.php
namespace App\Service;

use GuzzleHttp\Client;

class OcrSpaceService
{
    private $apiKey;
    private $client;

    public function __construct(string $apiKey = '')
    {
        $this->apiKey = $apiKey;
        $this->client = new Client(['base_uri' => 'https://api.ocr.space/parse/']);
    }

    public function extractTextFromFile(string $filePath): string
    {
        $response = $this->client->post('image', [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($filePath, 'r'),
                ],
                [
                    'name' => 'apikey',
                    'contents' => $this->apiKey,
                ],
                [
                    'name' => 'language',
                    'contents' => 'fre', // Français
                ],
            ],
        ]);

        $result = json_decode($response->getBody(), true);
        return $result['ParsedResults'][0]['ParsedText'] ?? '';
    }
}
