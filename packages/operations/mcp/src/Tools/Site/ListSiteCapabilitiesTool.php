<?php

declare(strict_types=1);

namespace Capell\Mcp\Tools\Site;

use Capell\Mcp\Data\AuthenticatedMcpClientData;
use Capell\Mcp\Enums\CapabilityServerEnum;
use Capell\Mcp\Support\CapellMcpCapabilityRegistry;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('capell-site-list-capabilities')]
#[Title('List Site Capabilities')]
#[Description('List installed Capell MCP capabilities visible to the authenticated MCP client.')]
#[IsReadOnly]
final class ListSiteCapabilitiesTool extends Tool
{
    public function handle(CapellMcpCapabilityRegistry $registry, AuthenticatedMcpClientData $client): ResponseFactory
    {
        return Response::structured([
            'capabilities' => $registry
                ->visibleFor(CapabilityServerEnum::Site, $client->scopes)
                ->map(fn ($capability): array => $capability->toPayload())
                ->all(),
        ]);
    }
}
