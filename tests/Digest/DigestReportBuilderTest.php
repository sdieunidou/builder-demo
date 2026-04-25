<?php

declare(strict_types=1);

namespace App\Tests\Digest;

use App\Digest\DigestReportBuilder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DigestReportBuilderTest extends TestCase
{
    public function testBuildForDateReturnsExpectedKpiKeys(): void
    {
        $conn = $this->createMock(\Doctrine\DBAL\Connection::class);
        $conn->method('fetchOne')->willReturn('0');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($conn);

        $builder = new DigestReportBuilder($em);
        $report = $builder->buildForDate(new \DateTimeImmutable('2026-01-15'));

        $this->assertArrayHasKey('report_date', $report);
        $this->assertArrayHasKey('new_users', $report);
        $this->assertArrayHasKey('total_users', $report);
    }

    public function testBuildForDateHandlesZeroActivityDay(): void
    {
        $conn = $this->createMock(\Doctrine\DBAL\Connection::class);
        $conn->method('fetchOne')->willReturn('0');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($conn);

        $builder = new DigestReportBuilder($em);

        // Should not throw
        $report = $builder->buildForDate(new \DateTimeImmutable('2026-01-01'));

        $this->assertSame('2026-01-01', $report['report_date']);
        $this->assertSame(0, $report['new_users']);
        $this->assertSame(0, $report['total_users']);
    }

    public function testBuildForDateFormatsReportDateCorrectly(): void
    {
        $conn = $this->createMock(\Doctrine\DBAL\Connection::class);
        $conn->method('fetchOne')->willReturn('5');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($conn);

        $builder = new DigestReportBuilder($em);
        $report = $builder->buildForDate(new \DateTimeImmutable('2026-04-25'));

        $this->assertSame('2026-04-25', $report['report_date']);
    }
}
