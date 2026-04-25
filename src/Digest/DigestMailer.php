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

    /**
     * Sends the weekly KPI report email to the given user.
     *
     * @param array<string, mixed> $kpis
     */
    public function sendWeekly(User $user, array $kpis): void
    {
        $subject = sprintf('Weekly Report — %s – %s', $kpis['week_start'] ?? 'N/A', $kpis['week_end'] ?? 'N/A');

        $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFrom))
            ->to(new Address($user->getEmail()))
            ->subject($subject)
            ->htmlTemplate('email/weekly_report.html.twig')
            ->textTemplate('email/weekly_report.txt.twig')
            ->context(['kpis' => $kpis]);

        $this->mailer->send($email);
    }
}
