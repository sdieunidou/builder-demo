<?php

declare(strict_types=1);

namespace App\Command;

use App\Dashboard\WeeklyKpiBuilder;
use App\Digest\DigestMailerInterface;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command that sends the weekly KPI report email to all subscribed users.
 *
 * Intended to be triggered by a system cron every Monday at 07:00.
 */
#[AsCommand(
    name: 'app:report:weekly',
    description: 'Send the weekly KPI report email to all subscribed users.',
)]
final class WeeklyReportScheduledCommand extends Command
{
    public function __construct(
        private readonly WeeklyKpiBuilder $kpiBuilder,
        private readonly DigestMailerInterface $mailer,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print recipients without sending emails.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $kpis   = $this->kpiBuilder->build();
        $dryRun = (bool) $input->getOption('dry-run');

        $subscribedUsers = $this->userRepository->findBy(['digestSubscribed' => true]);

        foreach ($subscribedUsers as $user) {
            if ($dryRun) {
                $output->writeln(sprintf('Would send to %s', $user->getEmail()));
                continue;
            }
            $this->mailer->sendWeekly($user, $kpis);
        }

        if (!$dryRun) {
            $output->writeln(sprintf(
                'Weekly report sent to %d user(s) for week %s – %s.',
                count($subscribedUsers),
                $kpis['week_start'],
                $kpis['week_end']
            ));
        }

        return Command::SUCCESS;
    }
}
