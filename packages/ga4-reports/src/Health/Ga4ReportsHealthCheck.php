<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class Ga4ReportsHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
