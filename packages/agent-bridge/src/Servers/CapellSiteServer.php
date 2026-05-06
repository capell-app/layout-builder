<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Servers;

use Capell\AgentBridge\Tools\Site\ConfirmSiteCapabilityTool;
use Capell\AgentBridge\Tools\Site\InspectSiteStateTool;
use Capell\AgentBridge\Tools\Site\ListSiteCapabilitiesTool;
use Capell\AgentBridge\Tools\Site\RunSiteCapabilityTool;
use Laravel\AgentBridge\Server;
use Laravel\AgentBridge\Server\Attributes\Instructions;
use Laravel\AgentBridge\Server\Attributes\Name;
use Laravel\AgentBridge\Server\Attributes\Version;

#[Name('Capell Site Agent Bridge')]
#[Version('0.1.0')]
#[Instructions('Authenticated Agent Bridge server for inspecting and safely operating an installed Capell CMS site.')]
final class CapellSiteServer extends Server
{
    protected array $tools = [
        ListSiteCapabilitiesTool::class,
        InspectSiteStateTool::class,
        RunSiteCapabilityTool::class,
        ConfirmSiteCapabilityTool::class,
    ];
}
