<?php

declare(strict_types=1);

namespace App\Tests\Export;

use App\Export\CsvReportExporter;
use PHPUnit\Framework\TestCase;

class CsvReportExporterTest extends TestCase
{
    private CsvReportExporter $exporter;

    protected function setUp(): void
    {
        $this->exporter = new CsvReportExporter();
    }

    public function testSingleDataRowOutputMatchesExpectedCsv(): void
    {
        $headers = ['week_start', 'week_end', 'new_users', 'total_users'];
        $rows    = [['2026-04-20', '2026-04-26', '5', '100']];

        $csv   = $this->exporter->export($headers, $rows);
        $lines = explode("\n", trim($csv));

        $this->assertCount(2, $lines);
        $this->assertSame('week_start,week_end,new_users,total_users', $lines[0]);
        $this->assertSame('2026-04-20,2026-04-26,5,100', $lines[1]);
    }

    public function testMultipleRowsLineCountEqualsRowsPlusHeader(): void
    {
        $headers = ['report_date', 'new_users', 'total_users'];
        $rows    = [
            ['2026-04-23', '3', '50'],
            ['2026-04-24', '1', '51'],
            ['2026-04-25', '2', '53'],
        ];

        $csv   = $this->exporter->export($headers, $rows);
        $lines = array_filter(explode("\n", $csv), fn($l) => $l !== '');

        $this->assertCount(count($rows) + 1, $lines);
    }

    public function testValueContainingCommaAndQuoteIsEscapedCorrectly(): void
    {
        $headers = ['name', 'value'];
        $rows    = [['He said, "hello"', 'normal']];

        $csv   = $this->exporter->export($headers, $rows);
        $lines = explode("\n", trim($csv));

        // fputcsv wraps fields containing commas/quotes in double-quotes and escapes inner quotes
        $this->assertStringContainsString('"He said, ""hello"""', $lines[1]);
    }

    public function testEmptyRowsArrayEmitsOnlyHeaderRow(): void
    {
        $headers = ['week_start', 'week_end', 'new_users', 'total_users'];
        $rows    = [];

        $csv   = $this->exporter->export($headers, $rows);
        $lines = array_filter(explode("\n", $csv), fn($l) => $l !== '');

        $this->assertCount(1, $lines);
        $this->assertSame('week_start,week_end,new_users,total_users', array_values($lines)[0]);
    }

    public function testCellStartingWithEqualSignIsPrefixedWithApostrophe(): void
    {
        $headers = ['formula', 'safe'];
        $rows    = [['=SUM(A1:A10)', 'normal']];

        $csv   = $this->exporter->export($headers, $rows);
        $lines = explode("\n", trim($csv));

        // The sanitised cell should start with ' to prevent injection
        $this->assertStringContainsString("'=SUM(A1:A10)", $lines[1]);
    }
}
