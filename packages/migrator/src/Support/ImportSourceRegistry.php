<?php

declare(strict_types=1);

namespace Capell\Migrator\Support;

use Capell\Migrator\Contracts\ImportSourceReader;
use RuntimeException;

final class ImportSourceRegistry
{
    /** @var list<ImportSourceReader> */
    private array $readers = [];

    public function register(ImportSourceReader $reader, bool $prepend = false): void
    {
        if ($prepend) {
            array_unshift($this->readers, $reader);

            return;
        }

        $this->readers[] = $reader;
    }

    public function readerFor(string $path): ImportSourceReader
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        foreach ($this->readers as $reader) {
            if ($reader->supports($extension)) {
                return $reader;
            }
        }

        throw new RuntimeException(sprintf('No import source reader is registered for [%s].', $extension));
    }

    /**
     * @return list<ImportSourceReader>
     */
    public function readers(): array
    {
        return $this->readers;
    }
}
