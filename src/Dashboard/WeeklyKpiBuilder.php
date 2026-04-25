<?php

declare(strict_types=1);

namespace App\Dashboard;

use App\Repository\UserRepository;

final class WeeklyKpiBuilder
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $now   = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $start = $now->modify('monday this week midnight');

        $newUsers   = $this->userRepository->countCreatedBetween($start, $now);
        $totalUsers = $this->userRepository->countAll();

        return [
            'new_users'   => $newUsers,
            'total_users' => $totalUsers,
            'week_start'  => $start->format('d M Y'),
            'week_end'    => $start->modify('+6 days')->format('d M Y'),
        ];
    }
}
