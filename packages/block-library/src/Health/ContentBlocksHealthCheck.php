<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class ContentBlocksHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
