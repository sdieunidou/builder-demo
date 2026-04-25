<?php

declare(strict_types=1);

namespace App\Export;

final class CsvReportExporter
{
    /**
     * @param list<string>              $headers
     * @param list<array<string,mixed>> $rows
     */
    public function export(array $headers, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers, separator: ',', enclosure: '"', escape: '\\');
        foreach ($rows as $row) {
            $cells = array_map(fn($v) => $this->sanitise((string) $v), $row);
            fputcsv($handle, $cells, separator: ',', enclosure: '"', escape: '\\');
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    private function sanitise(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
