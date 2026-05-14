<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Corporate\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class ThemeCorporateHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
