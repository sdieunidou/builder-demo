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
}
