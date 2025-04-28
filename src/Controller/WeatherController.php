<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Annotation\Route;

class WeatherController extends AbstractController
{
    #[Route('/weather/{city}', name: 'weather_city')]
    public function weather(string $city = 'Paris'): Response
    {
        $apiKey = 'b56ce80f1e74973836f961ebb25d8704';
        $client = HttpClient::create();

        $response = $client->request(
            'GET',
            'https://api.openweathermap.org/data/2.5/weather', [
                'query' => [
                    'q' => $city,
                    'appid' => $apiKey,
                    'units' => 'metric', // Celsius
                    'lang' => 'fr' // French description
                ]
            ]
        );

        $data = $response->toArray();

        return $this->render('weather/index.html.twig', [
            'city' => $data['name'],
            'temp' => $data['main']['temp'],
            'description' => $data['weather'][0]['description'],
        ]);
    }
}
