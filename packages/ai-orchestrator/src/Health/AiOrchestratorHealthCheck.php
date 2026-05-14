<?php

declare(strict_types=1);

namespace Capell\AIOrchestrator\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class AiOrchestratorHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
