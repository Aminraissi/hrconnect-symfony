<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use App\Repository\UserRepository;

class BackOfficeController extends AbstractController
{
    #[Route('/back-office', name: 'app_back_office')]
    public function index(UserRepository $userRepository): Response
    {
        // Weather API request
        $apiKey = 'b56ce80f1e74973836f961ebb25d8704';
        $client = HttpClient::create();

        $response = $client->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
            'query' => [
                'q' => 'Tunis', // You can change the city if needed
                'appid' => $apiKey,
                'units' => 'metric',
                'lang' => 'fr'
            ]
        ]);

        $data = $response->toArray();

        // Get counts from the database

        // Total number of users
        $totalUsers = $userRepository->createQueryBuilder('u')
            ->select('count(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Count users where roles contain ROLE_ADMIN.
        $adminUsers = $userRepository->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where("u.roles LIKE :role")
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();

        // Count users where roles contain ROLE_INACTIVE.
        $inactiveUsers = $userRepository->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where("u.roles LIKE :role")
            ->setParameter('role', '%ROLE_INACTIVE%')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('back_office/index.html.twig', [
            'controller_name' => 'BackOfficeController',
            'city' => $data['name'],
            'temp' => $data['main']['temp'],
            'description' => $data['weather'][0]['description'],
            'totalUsers' => $totalUsers,
            'adminUsers' => $adminUsers,
            'inactiveUsers' => $inactiveUsers,
        ]);
    }

    #[Route('/inactive-redirect', name: 'inactive_redirect')]
    public function inactiveRedirect(): Response
    {
        return $this->render('back_office/inactive.html.twig');
    }
}
