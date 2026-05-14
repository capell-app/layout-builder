<?php

declare(strict_types=1);

namespace Capell\Navigation\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class NavigationHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
