<?php

declare(strict_types=1);

namespace Capell\Backup\Services\Import;

use Capell\Backup\Exceptions\NotImplementedException;

/**
 * H4 placeholder. Reads CSV/XLSX spreadsheet imports into the shared
 * backup payload shape.
 */
final class SpreadsheetReader
{
    public function read(): never
    {
        throw NotImplementedException::forPhase('H4', self::class);
    }
}
