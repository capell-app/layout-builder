<?php

declare(strict_types=1);

namespace Capell\Backup\Services\Import;

use Capell\Backup\Exceptions\NotImplementedException;

/**
 * H4 placeholder. Translates external field names (WordPress, CSV
 * headers, etc.) onto the Capell page/type schema.
 */
final class FieldMapper
{
    /**
     * @return array<string, mixed>
     */
    public function map(): array
    {
        throw NotImplementedException::forPhase('H4', self::class);
    }
}
