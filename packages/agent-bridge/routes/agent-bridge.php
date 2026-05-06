<?php

declare(strict_types=1);

use Capell\AgentBridge\Http\Middleware\AuthenticateCapellAgentBridgeToken;
use Capell\AgentBridge\Servers\CapellKnowledgeServer;
use Capell\AgentBridge\Servers\CapellSiteServer;
use Illuminate\Support\Facades\Route;
use Laravel\AgentBridge\Facades\AgentBridge;

$homeRoute = config('capell-agent-bridge.routes.home', '/');
$knowledgeRoute = config('capell-agent-bridge.routes.knowledge', 'agent-bridge/capell/knowledge');
$siteRoute = config('capell-agent-bridge.routes.site', 'agent-bridge/capell');

if (is_string($homeRoute) && $homeRoute !== '') {
    $servers = [];

    if (is_string($knowledgeRoute) && $knowledgeRoute !== '') {
        $servers['knowledge'] = url($knowledgeRoute);
    }

    if (is_string($siteRoute) && $siteRoute !== '') {
        $servers['site'] = url($siteRoute);
    }

    Route::get($homeRoute, static fn (): array => [
        'name' => 'Capell Agent Bridge',
        'status' => 'ok',
        'servers' => $servers,
    ])->name('capell-agent-bridge.home');
}

if (class_exists(AgentBridge::class)) {
    if (is_string($knowledgeRoute) && $knowledgeRoute !== '') {
        AgentBridge::web($knowledgeRoute, CapellKnowledgeServer::class);
    }

    if (is_string($siteRoute) && $siteRoute !== '') {
        AgentBridge::web($siteRoute, CapellSiteServer::class)
            ->middleware(AuthenticateCapellAgentBridgeToken::class);
    }
}
