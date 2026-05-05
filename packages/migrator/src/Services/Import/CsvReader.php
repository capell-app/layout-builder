<?php

declare(strict_types=1);

namespace Capell\Migrator\Services\Import;

use Capell\Migrator\Contracts\ImportSourceReader;
use Capell\Migrator\Data\ExternalImportReadResult;
use RuntimeException;
use SplFileObject;

final class CsvReader implements ImportSourceReader
{
    public function supports(string $extension): bool
    {
        return in_array(strtolower($extension), ['csv', 'txt'], true);
    }

    public function read(string $path): ExternalImportReadResult
    {
        if (! is_readable($path)) {
            throw new RuntimeException(sprintf('CSV import source [%s] is not readable.', $path));
        }

        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

        $columns = [];
        $rows = [];

        foreach ($file as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            if ($row === [null]) {
                continue;
            }

            $values = array_map(static fn (mixed $value): string => trim((string) $value), $row);

            if ($index === 0) {
                $columns = array_values(array_filter($values, static fn (string $value): bool => $value !== ''));

                continue;
            }

            throw_if($columns === [], RuntimeException::class, 'CSV import source must include a header row.');

            $rows[] = array_combine(
                $columns,
                array_pad(array_slice($values, 0, count($columns)), count($columns), ''),
            );
        }

        return new ExternalImportReadResult(
            sourceType: 'csv',
            columns: $columns,
            rows: $rows,
            metadata: ['filename' => basename($path)],
        );
    }
}
