<?php

declare(strict_types=1);

namespace Capell\Mcp\Contracts;

use Capell\Mcp\Support\CapellMcpCapabilityRegistry;

interface CapellMcpCapabilityProvider
{
    public function registerCapabilities(CapellMcpCapabilityRegistry $registry): void;
}
