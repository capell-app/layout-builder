<?php

declare(strict_types=1);

namespace Capell\Mcp\Tools\Boost;

use Capell\Mcp\Data\CapabilityData;
use Capell\Mcp\Enums\CapabilityServerEnum;
use Capell\Mcp\Support\CapellMcpCapabilityRegistry;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('capell-list-capabilities')]
#[Title('List Capell Capabilities')]
#[Description('List installed Capell MCP capabilities available through the Laravel Boost MCP server.')]
#[IsReadOnly]
final class ListBoostCapabilitiesTool extends Tool
{
    public function handle(CapellMcpCapabilityRegistry $registry): ResponseFactory
    {
        return Response::structured([
            'capabilities' => $registry
                ->visibleFor(CapabilityServerEnum::Site)
                ->map(fn (CapabilityData $capability): array => $capability->toPayload())
                ->all(),
            'confirmation' => 'Use the authenticated Capell Site MCP server for confirmed mutating operations.',
        ]);
    }
}
