<?php

declare(strict_types=1);

namespace Capell\HtmlCache\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class HtmlCacheHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
