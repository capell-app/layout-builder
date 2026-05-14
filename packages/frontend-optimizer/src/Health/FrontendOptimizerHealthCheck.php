<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class FrontendOptimizerHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
