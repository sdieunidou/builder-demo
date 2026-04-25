<?php

declare(strict_types=1);

namespace App\Digest;

use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command that sends the daily digest email to all subscribed users.
 *
 * Intended to be triggered by a system cron once per day (e.g. at 07:00).
 */
#[AsCommand(
    name: 'app:digest:send',
    description: 'Send the daily digest email to all subscribed users.',
)]
final class DigestScheduledCommand extends Command
{
    public function __construct(
        private readonly DigestReportBuilderInterface $reportBuilder,
        private readonly DigestMailerInterface $digestMailer,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $yesterday = new \DateTimeImmutable('yesterday');
        $report = $this->reportBuilder->buildForDate($yesterday);

        $subscribedUsers = $this->userRepository->findBy(['digestSubscribed' => true]);

        foreach ($subscribedUsers as $user) {
            $this->digestMailer->sendDigest($user, $report);
        }

        $output->writeln(sprintf(
            'Digest sent to %d user(s) for date %s.',
            count($subscribedUsers),
            $yesterday->format('Y-m-d')
        ));

        return Command::SUCCESS;
    }
}
