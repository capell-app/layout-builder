<?php

declare(strict_types=1);

namespace Capell\Migrator\Contracts;

use Capell\Migrator\Data\ExternalImportReadResult;

interface ImportSourceReader
{
    public function supports(string $extension): bool;

    public function read(string $path): ExternalImportReadResult;
}
