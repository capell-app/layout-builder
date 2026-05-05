<?php

declare(strict_types=1);

namespace Capell\Migrator\Services\Import;

use Capell\Migrator\Data\ExternalImportReadResult;

/**
 * Reads spreadsheet-style imports into the shared migrator source shape.
 * Native support is CSV only; XLSX remains intentionally out of scope until
 * the package earns a spreadsheet dependency.
 */
final readonly class SpreadsheetReader
{
    public function __construct(private CsvReader $csvReader = new CsvReader) {}

    public function read(string $path): ExternalImportReadResult
    {
        return $this->csvReader->read($path);
    }
}
