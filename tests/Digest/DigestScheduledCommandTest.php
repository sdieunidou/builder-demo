<?php

declare(strict_types=1);

namespace App\Tests\Digest;

use App\Digest\DigestMailerInterface;
use App\Digest\DigestReportBuilderInterface;
use App\Digest\DigestScheduledCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DigestScheduledCommandTest extends TestCase
{
    private function buildUser(string $email, bool $subscribed): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('hashed');
        $user->setDigestSubscribed($subscribed);

        return $user;
    }

    private function makeReportBuilder(): DigestReportBuilderInterface
    {
        $builder = $this->createMock(DigestReportBuilderInterface::class);
        $builder->method('buildForDate')->willReturn([
            'report_date' => 'yesterday',
            'new_users' => 0,
            'total_users' => 1,
        ]);

        return $builder;
    }

    public function testCommandReturnsSuccess(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('findBy')->willReturn([]);

        $mailer = $this->createMock(DigestMailerInterface::class);
        $mailer->expects($this->never())->method('sendDigest');

        $command = new DigestScheduledCommand($this->makeReportBuilder(), $mailer, $repo);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testCommandSkipsUsersWhereDigestSubscribedIsFalse(): void
    {
        $subscribedUser = $this->buildUser('yes@example.com', true);
        $unsubscribedUser = $this->buildUser('no@example.com', false);

        // UserRepository::findBy(['digestSubscribed' => true]) should only return subscribed users
        $repo = $this->createMock(UserRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['digestSubscribed' => true])
            ->willReturn([$subscribedUser]);

        $mailer = $this->createMock(DigestMailerInterface::class);
        $mailer->expects($this->once())
            ->method('sendDigest')
            ->with($subscribedUser, $this->anything());

        $command = new DigestScheduledCommand($this->makeReportBuilder(), $mailer, $repo);
        $tester = new CommandTester($command);
        $tester->execute([]);

        // Verify unsubscribed user was not passed to sendDigest (implicit via once() above)
        $this->assertTrue(true);
    }

    public function testCommandCallsSendDigestOncePerSubscribedUser(): void
    {
        $users = [
            $this->buildUser('user1@example.com', true),
            $this->buildUser('user2@example.com', true),
            $this->buildUser('user3@example.com', true),
        ];

        $repo = $this->createMock(UserRepository::class);
        $repo->method('findBy')->with(['digestSubscribed' => true])->willReturn($users);

        $mailer = $this->createMock(DigestMailerInterface::class);
        $mailer->expects($this->exactly(3))->method('sendDigest');

        $command = new DigestScheduledCommand($this->makeReportBuilder(), $mailer, $repo);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }
}
