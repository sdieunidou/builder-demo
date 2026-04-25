<?php

declare(strict_types=1);

namespace App\Digest;

interface DigestReportBuilderInterface
{
    /**
     * Returns an associative array of KPI key => value for the given date.
     *
     * @return array<string, mixed>
     */
    public function buildForDate(\DateTimeImmutable $date): array;
}
