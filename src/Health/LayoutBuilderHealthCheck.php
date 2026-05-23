<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class LayoutBuilderHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
