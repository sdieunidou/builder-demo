<?php

declare(strict_types=1);

namespace App\Tests\Digest;

use App\Digest\DigestMailer;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class DigestMailerTest extends TestCase
{
    private function buildUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('hashed');

        return $user;
    }

    public function testSendDigestDispatchesExactlyOneMessage(): void
    {
        $sentMessages = [];

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->willReturnCallback(static function (RawMessage $msg) use (&$sentMessages): void {
                $sentMessages[] = $msg;
            });

        $digestMailer = new DigestMailer($mailer, 'noreply@example.com');
        $digestMailer->sendDigest(
            $this->buildUser('user@example.com'),
            ['report_date' => '2026-04-24', 'new_users' => 3, 'total_users' => 10]
        );

        $this->assertCount(1, $sentMessages);
    }

    public function testSendDigestSubjectContainsReportDate(): void
    {
        $capturedEmail = null;

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->willReturnCallback(static function (RawMessage $msg) use (&$capturedEmail): void {
                $capturedEmail = $msg;
            });

        $digestMailer = new DigestMailer($mailer, 'noreply@example.com');
        $digestMailer->sendDigest(
            $this->buildUser('user@example.com'),
            ['report_date' => '2026-04-24', 'new_users' => 0, 'total_users' => 5]
        );

        $this->assertNotNull($capturedEmail);
        /** @var Email $capturedEmail */
        $this->assertStringContainsString('2026-04-24', $capturedEmail->getSubject());
    }

    public function testSendDigestEmailHasBothHtmlAndTextTemplates(): void
    {
        $capturedEmail = null;

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->willReturnCallback(static function (RawMessage $msg) use (&$capturedEmail): void {
                $capturedEmail = $msg;
            });

        $digestMailer = new DigestMailer($mailer, 'noreply@example.com');
        $digestMailer->sendDigest(
            $this->buildUser('user@example.com'),
            ['report_date' => '2026-04-24', 'new_users' => 1, 'total_users' => 2]
        );

        $this->assertNotNull($capturedEmail);
        /** @var \Symfony\Bridge\Twig\Mime\TemplatedEmail $capturedEmail */
        $this->assertSame('digest/daily_email.html.twig', $capturedEmail->getHtmlTemplate());
        $this->assertSame('digest/daily_email.txt.twig', $capturedEmail->getTextTemplate());
    }
}
