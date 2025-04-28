<?php
// src/Service/WeatherService.php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    private $client;
    private $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    public function getWeatherForecast(string $location, int $days = 7): array
    {
        $response = $this->client->request(
            'GET',
            'http://api.weatherapi.com/v1/forecast.json',
            [
                'query' => [
                    'key' => $this->apiKey,
                    'q' => $location,
                    'days' => $days,
                    'lang' => 'fr'
                ]
            ]
        );

        return $response->toArray();
    }
}