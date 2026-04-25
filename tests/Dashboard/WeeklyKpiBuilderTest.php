<?php

declare(strict_types=1);

namespace App\Tests\Dashboard;

use App\Dashboard\WeeklyKpiBuilder;
use App\Repository\UserRepository;
use PHPUnit\Framework\TestCase;

class WeeklyKpiBuilderTest extends TestCase
{
    public function testBuildReturnsCorrectKpisForKnownWeek(): void
    {
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('countCreatedBetween')->willReturn(7);
        $userRepo->method('countAll')->willReturn(42);

        $builder = new WeeklyKpiBuilder($userRepo);
        $result  = $builder->build();

        $this->assertArrayHasKey('new_users', $result);
        $this->assertArrayHasKey('total_users', $result);
        $this->assertArrayHasKey('week_start', $result);
        $this->assertArrayHasKey('week_end', $result);

        $this->assertSame(7, $result['new_users']);
        $this->assertSame(42, $result['total_users']);
    }

    public function testBuildReturnsZeroNewUsersForEmptyWeek(): void
    {
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('countCreatedBetween')->willReturn(0);
        $userRepo->method('countAll')->willReturn(100);

        $builder = new WeeklyKpiBuilder($userRepo);
        $result  = $builder->build();

        $this->assertSame(0, $result['new_users']);
        $this->assertSame(100, $result['total_users']);
    }
}
