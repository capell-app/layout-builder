<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Resources;

use Laravel\AgentBridge\Response;
use Laravel\AgentBridge\Server\Attributes\Description;
use Laravel\AgentBridge\Server\Attributes\MimeType;
use Laravel\AgentBridge\Server\Attributes\Name;
use Laravel\AgentBridge\Server\Attributes\Title;
use Laravel\AgentBridge\Server\Attributes\Uri;
use Laravel\AgentBridge\Server\Resource;

#[Name('capell-agent-bridge-overview')]
#[Title('Capell Agent Bridge Overview')]
#[Description('Overview of the Capell Agent Bridge two-server model and site capability workflow.')]
#[Uri('capell://agent-bridge/overview')]
#[MimeType('text/markdown')]
final class CapellAgentBridgeOverviewResource extends Resource
{
    public function handle(): Response
    {
        return Response::text(<<<'MARKDOWN'
            # Capell Agent Bridge

            Capell Agent Bridge uses two servers:

            - `CapellKnowledgeServer` is public and read-only.
            - `CapellSiteServer` is installed into a Capell site and requires bearer-token authentication.

            Site actions are registered capabilities. Mutating capabilities return a preview and confirmation token before execution.
            MARKDOWN);
    }
}
