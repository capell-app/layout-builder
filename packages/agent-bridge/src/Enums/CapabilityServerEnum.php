<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Enums;

enum CapabilityServerEnum: string
{
    case Knowledge = 'knowledge';
    case Site = 'site';
    case Both = 'both';

    public function isVisibleOn(self $server): bool
    {
        return $this === self::Both || $this === $server;
    }
}
