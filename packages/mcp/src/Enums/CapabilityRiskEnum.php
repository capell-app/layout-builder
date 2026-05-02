<?php

declare(strict_types=1);

namespace Capell\Mcp\Enums;

enum CapabilityRiskEnum: string
{
    case Read = 'read';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Destructive = 'destructive';

    public function requiresConfirmation(): bool
    {
        return $this !== self::Read;
    }
}
