<?php

declare(strict_types=1);

namespace App\Digest;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Renders and dispatches the daily digest email to a single user.
 */
final class DigestMailer implements DigestMailerInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $mailerFrom,
    ) {
    }

    /**
     * Sends the daily digest email to the given user with the provided report data.
     *
     * @param array<string, mixed> $report
     */
    public function sendDigest(User $user, array $report): void
    {
        $reportDate = $report['report_date'] ?? 'N/A';

        $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFrom))
            ->to(new Address($user->getEmail()))
            ->subject(sprintf('Daily Digest — %s', $reportDate))
            ->htmlTemplate('digest/daily_email.html.twig')
            ->textTemplate('digest/daily_email.txt.twig')
            ->context([
                'report' => $report,
                'report_date' => $reportDate,
            ]);

        $this->mailer->send($email);
    }
}
