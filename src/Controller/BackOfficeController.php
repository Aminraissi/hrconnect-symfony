<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;

class BackOfficeController extends AbstractController
{
    #[Route('/back-office', name: 'app_back_office')]
    public function index(): Response
    {
        $apiKey = 'b56ce80f1e74973836f961ebb25d8704';
        $client = HttpClient::create();

        $response = $client->request(
            'GET',
            'https://api.openweathermap.org/data/2.5/weather', [
                'query' => [
                    'q' => 'Tunis', // You can change the city
                    'appid' => $apiKey,
                    'units' => 'metric',
                    'lang' => 'fr'
                ]
            ]
        );

        $data = $response->toArray();

        return $this->render('back_office/index.html.twig', [
            'controller_name' => 'BackOfficeController',
            'city' => $data['name'],
            'temp' => $data['main']['temp'],
            'description' => $data['weather'][0]['description'],
        ]);
    }
}
