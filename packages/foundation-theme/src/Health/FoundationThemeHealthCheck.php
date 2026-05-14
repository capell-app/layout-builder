<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class FoundationThemeHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
