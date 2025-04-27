<?php

namespace App\Service;

use App\Repository\ReclamationRepository;

class ReclamationService
{
    private ReclamationRepository $repository;

    public function __construct(ReclamationRepository $repository)
    {
        $this->repository = $repository;
    }

    public function search(string $keyword): array
    {
        return $this->repository->createQueryBuilder('r')
            ->where('r.employeeName LIKE :kw')
            ->orWhere('r.type LIKE :kw')
            ->orWhere('r.status LIKE :kw')
            ->setParameter('kw', '%' . $keyword . '%')
            ->getQuery()
            ->getResult();
    }
}
