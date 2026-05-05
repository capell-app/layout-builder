<?php

declare(strict_types=1);

namespace Capell\Migrator\Services\Import;

use Capell\Migrator\Exceptions\NotImplementedException;

/**
 * H3 placeholder. Will delegate to PageImportService for page writes
 * and additionally materialise Site / SiteDomain shared relations from
 * the package.
 */
final class SiteImportService
{
    public function import(): never
    {
        throw NotImplementedException::forPhase('H3', self::class);
    }
}
