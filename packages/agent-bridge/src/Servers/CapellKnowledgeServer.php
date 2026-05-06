<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Servers;

use Capell\AgentBridge\Resources\CapellAgentBridgeOverviewResource;
use Capell\AgentBridge\Tools\Knowledge\ListKnowledgePackagesTool;
use Capell\AgentBridge\Tools\Knowledge\ReadKnowledgeDocumentTool;
use Capell\AgentBridge\Tools\Knowledge\RecommendPackagesTool;
use Laravel\AgentBridge\Server;
use Laravel\AgentBridge\Server\Attributes\Instructions;
use Laravel\AgentBridge\Server\Attributes\Name;
use Laravel\AgentBridge\Server\Attributes\Version;

#[Name('Capell Knowledge Agent Bridge')]
#[Version('0.1.0')]
#[Instructions('Read-only knowledge for Capell CMS, package metadata, docs, and implementation conventions. Enable this server only for authorised deployments.')]
final class CapellKnowledgeServer extends Server
{
    protected array $tools = [
        ListKnowledgePackagesTool::class,
        ReadKnowledgeDocumentTool::class,
        RecommendPackagesTool::class,
    ];

    protected array $resources = [
        CapellAgentBridgeOverviewResource::class,
    ];
}
