<?php

declare(strict_types=1);

namespace Capell\Backup\Services\Import;

use Capell\Backup\Exceptions\NotImplementedException;

/**
 * H4 placeholder. Parses WordPress WXR XML exports into the shared
 * backup payload shape so the normal mapping/validation flow can
 * take over.
 */
final class WpXmlReader
{
    public function read(): never
    {
        throw NotImplementedException::forPhase('H4', self::class);
    }
}
