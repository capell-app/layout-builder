<?php

declare(strict_types=1);

namespace Capell\MediaAI\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class MediaAIHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
