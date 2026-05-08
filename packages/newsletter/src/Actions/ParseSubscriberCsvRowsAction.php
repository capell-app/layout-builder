<?php

declare(strict_types=1);

namespace Capell\Newsletter\Actions;

use Lorisleiva\Actions\Concerns\AsAction;

class ParseSubscriberCsvRowsAction
{
    use AsAction;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(string $contents): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($contents));

        if (! is_array($lines) || $lines === []) {
            return [];
        }

        $headerLine = array_shift($lines);
        $headers = str_getcsv($headerLine);
        $headers = array_map(trim(...), $headers);

        $rows = [];

        foreach ($lines as $line) {
            if (! is_string($line)) {
                continue;
            }

            if (trim($line) === '') {
                continue;
            }

            $values = str_getcsv($line);
            $row = [];

            foreach ($headers as $headerIndex => $header) {
                if ($header === '') {
                    continue;
                }

                $row[$header] = $values[$headerIndex] ?? null;
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
