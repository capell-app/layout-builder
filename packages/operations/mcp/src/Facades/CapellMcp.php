<?php

declare(strict_types=1);

namespace Capell\Mcp\Facades;

use Capell\Mcp\Data\CapabilityData;
use Capell\Mcp\Support\CapellMcpCapabilityRegistry;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void register(CapabilityData $capability)
 * @method static CapabilityData get(string $key)
 * @method static bool has(string $key)
 *
 * @see CapellMcpCapabilityRegistry
 */
final class CapellMcp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CapellMcpCapabilityRegistry::class;
    }
}
