<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Saas\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class ThemeSaasHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
