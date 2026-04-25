<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\WeeklyReportScheduledCommand;
use App\Dashboard\WeeklyKpiBuilder;
use App\Digest\DigestMailerInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class WeeklyReportScheduledCommandTest extends TestCase
{
    private function buildUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('hashed');
        $user->setDigestSubscribed(true);

        return $user;
    }

    private function makeKpiBuilder(): WeeklyKpiBuilder
    {
        $builder = $this->createMock(WeeklyKpiBuilder::class);
        $builder->method('build')->willReturn([
            'new_users'   => 5,
            'total_users' => 100,
            'week_start'  => '21 Apr 2025',
            'week_end'    => '27 Apr 2025',
        ]);

        return $builder;
    }

    public function testHappyPathMailerCalledOncePerSubscribedUser(): void
    {
        $users = [
            $this->buildUser('alice@example.com'),
            $this->buildUser('bob@example.com'),
        ];

        $repo = $this->createMock(UserRepository::class);
        $repo->method('findBy')->with(['digestSubscribed' => true])->willReturn($users);

        $mailer = $this->createMock(DigestMailerInterface::class);
        $mailer->expects($this->exactly(2))->method('sendWeekly');

        $command = new WeeklyReportScheduledCommand($this->makeKpiBuilder(), $mailer, $repo);
        $tester  = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testDryRunMailerNeverCalled(): void
    {
        $users = [
            $this->buildUser('alice@example.com'),
            $this->buildUser('bob@example.com'),
        ];

        $repo = $this->createMock(UserRepository::class);
        $repo->method('findBy')->with(['digestSubscribed' => true])->willReturn($users);

        $mailer = $this->createMock(DigestMailerInterface::class);
        $mailer->expects($this->never())->method('sendWeekly');

        $command = new WeeklyReportScheduledCommand($this->makeKpiBuilder(), $mailer, $repo);
        $tester  = new CommandTester($command);
        $exitCode = $tester->execute(['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Would send to alice@example.com', $tester->getDisplay());
        $this->assertStringContainsString('Would send to bob@example.com', $tester->getDisplay());
    }

    public function testCommandSucceedsWithNoSubscribedUsers(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('findBy')->willReturn([]);

        $mailer = $this->createMock(DigestMailerInterface::class);
        $mailer->expects($this->never())->method('sendWeekly');

        $command = new WeeklyReportScheduledCommand($this->makeKpiBuilder(), $mailer, $repo);
        $tester  = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }
}
