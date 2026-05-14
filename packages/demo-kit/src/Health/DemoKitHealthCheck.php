<?php

declare(strict_types=1);

namespace Capell\DemoKit\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class DemoKitHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
