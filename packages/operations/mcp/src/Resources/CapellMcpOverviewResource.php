<?php

declare(strict_types=1);

namespace Capell\Mcp\Resources;

use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('capell-mcp-overview')]
#[Title('Capell MCP Overview')]
#[Description('Overview of the Capell MCP two-server model and site capability workflow.')]
#[Uri('capell://mcp/overview')]
#[MimeType('text/markdown')]
final class CapellMcpOverviewResource extends Resource
{
    public function handle(): Response
    {
        return Response::text(<<<'MARKDOWN'
            # Capell MCP

            Capell MCP uses two servers:

            - `CapellKnowledgeServer` is public and read-only.
            - `CapellSiteServer` is installed into a Capell site and requires bearer-token authentication.

            Site actions are registered capabilities. Mutating capabilities return a preview and confirmation token before execution.
            MARKDOWN);
    }
}
