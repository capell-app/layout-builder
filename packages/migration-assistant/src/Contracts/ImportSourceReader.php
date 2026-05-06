<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Contracts;

use Capell\MigrationAssistant\Data\ExternalImportReadResult;

interface ImportSourceReader
{
    public function supports(string $extension): bool;

    public function read(string $path): ExternalImportReadResult;
}
