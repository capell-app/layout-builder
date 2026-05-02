<?php

declare(strict_types=1);

namespace Capell\Mcp\Servers;

use Capell\Mcp\Resources\CapellMcpOverviewResource;
use Capell\Mcp\Tools\Knowledge\ListKnowledgePackagesTool;
use Capell\Mcp\Tools\Knowledge\ReadKnowledgeDocumentTool;
use Capell\Mcp\Tools\Knowledge\RecommendPackagesTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Capell Knowledge MCP')]
#[Version('0.1.0')]
#[Instructions('Read-only public knowledge for Capell CMS, package metadata, docs, and implementation conventions.')]
final class CapellKnowledgeServer extends Server
{
    protected array $tools = [
        ListKnowledgePackagesTool::class,
        ReadKnowledgeDocumentTool::class,
        RecommendPackagesTool::class,
    ];

    protected array $resources = [
        CapellMcpOverviewResource::class,
    ];
}
