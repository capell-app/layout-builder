<?php

declare(strict_types=1);

namespace Capell\Mcp\Servers;

use Capell\Mcp\Tools\Site\ConfirmSiteCapabilityTool;
use Capell\Mcp\Tools\Site\InspectSiteStateTool;
use Capell\Mcp\Tools\Site\ListSiteCapabilitiesTool;
use Capell\Mcp\Tools\Site\RunSiteCapabilityTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Capell Site MCP')]
#[Version('0.1.0')]
#[Instructions('Authenticated MCP server for inspecting and safely operating an installed Capell CMS site.')]
final class CapellSiteServer extends Server
{
    protected array $tools = [
        ListSiteCapabilitiesTool::class,
        InspectSiteStateTool::class,
        RunSiteCapabilityTool::class,
        ConfirmSiteCapabilityTool::class,
    ];
}
