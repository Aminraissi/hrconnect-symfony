<?php

namespace App\Repository;

use App\Entity\ParticipationSeminaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ParticipationSeminaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParticipationSeminaire::class);
    }

    /**
     * Get distribution of participation statuses across all seminars.
     */
    public function getStatusDistribution(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.statut as status, COUNT(p.id) as count')
            ->groupBy('p.statut');

        $results = $qb->getQuery()->getResult();

        $statuses = ['Inscrit', 'présent', 'absent', 'en attente'];
        $data = array_fill_keys($statuses, 0);

        foreach ($results as $result) {
            $data[$result['status']] = (int) $result['count'];
        }

        return $data;
    }

    /**
     * Get cost range stats across all seminars.
     */
    public function getCostRangeStats(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select("
                CASE
                    WHEN s.cout < 100 THEN '0-100 DT'
                    WHEN s.cout < 200 THEN '100-200 DT'
                    WHEN s.cout < 300 THEN '200-300 DT'
                    ELSE '300+ DT'
                END as cost_range,
                COUNT(p.id) as count
            ")
            ->join('p.seminaire', 's')
            ->groupBy('cost_range');

        $results = $qb->getQuery()->getResult();

        $data = [
            '0-100 DT' => 0,
            '100-200 DT' => 0,
            '200-300 DT' => 0,
            '300+ DT' => 0
        ];
        foreach ($results as $result) {
            $data[$result['cost_range']] = (int) $result['count'];
        }

        return $data;
    }

    /**
     * Get inscription trend over time across all seminars.
     */
    public function getInscriptionTrend(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.dateInscription as date, COUNT(p.id) as count')
            ->groupBy('p.dateInscription')
            ->orderBy('p.dateInscription', 'ASC');

        $results = $qb->getQuery()->getResult();

        $labels = [];
        $data = [];
        foreach ($results as $result) {
            $labels[] = $result['date']->format('Y-m-d');
            $data[] = (int) $result['count'];
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
}