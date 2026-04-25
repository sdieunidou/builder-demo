<?php

declare(strict_types=1);

namespace App\Digest;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Aggregates KPI data for a given date to be included in the daily digest email.
 */
final class DigestReportBuilder implements DigestReportBuilderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Returns an associative array of KPI key => value for the given date.
     *
     * @return array<string, mixed>
     */
    public function buildForDate(\DateTimeImmutable $date): array
    {
        $start = $date->setTime(0, 0, 0);
        $end = $date->setTime(23, 59, 59);

        $conn = $this->entityManager->getConnection();

        $newUsers = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM "user" WHERE created_at >= :start AND created_at <= :end',
            [
                'start' => $start->format('Y-m-d H:i:sO'),
                'end' => $end->format('Y-m-d H:i:sO'),
            ]
        );

        $totalUsers = (int) $conn->fetchOne('SELECT COUNT(*) FROM "user"');

        return [
            'report_date' => $date->format('Y-m-d'),
            'new_users' => $newUsers,
            'total_users' => $totalUsers,
        ];
    }
}
