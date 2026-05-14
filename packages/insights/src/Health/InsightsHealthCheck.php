<?php

declare(strict_types=1);

namespace Capell\Insights\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class InsightsHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
