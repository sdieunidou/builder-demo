<?php

declare(strict_types=1);

namespace App\Digest;

use App\Entity\User;

interface DigestMailerInterface
{
    /**
     * Sends the daily digest email to the given user with the provided report data.
     *
     * @param array<string, mixed> $report
     */
    public function sendDigest(User $user, array $report): void;

    /**
     * Sends the weekly KPI report email to the given user.
     *
     * @param array<string, mixed> $kpis
     */
    public function sendWeekly(User $user, array $kpis): void;
}
