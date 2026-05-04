<?php

declare(strict_types=1);

use Capell\Mcp\Http\Middleware\AuthenticateCapellMcpToken;
use Capell\Mcp\Servers\CapellKnowledgeServer;
use Capell\Mcp\Servers\CapellSiteServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

$homeRoute = config('capell-mcp.routes.home', '/');
$knowledgeRoute = config('capell-mcp.routes.knowledge', 'mcp/capell/knowledge');
$siteRoute = config('capell-mcp.routes.site', 'mcp/capell');

if (is_string($homeRoute) && $homeRoute !== '') {
    $servers = [];

    if (is_string($knowledgeRoute) && $knowledgeRoute !== '') {
        $servers['knowledge'] = url($knowledgeRoute);
    }

    if (is_string($siteRoute) && $siteRoute !== '') {
        $servers['site'] = url($siteRoute);
    }

    Route::get($homeRoute, static fn (): array => [
        'name' => 'Capell MCP',
        'status' => 'ok',
        'servers' => $servers,
    ])->name('capell-mcp.home');
}

if (class_exists(Mcp::class)) {
    if (is_string($knowledgeRoute) && $knowledgeRoute !== '') {
        Mcp::web($knowledgeRoute, CapellKnowledgeServer::class);
    }

    if (is_string($siteRoute) && $siteRoute !== '') {
        Mcp::web($siteRoute, CapellSiteServer::class)
            ->middleware(AuthenticateCapellMcpToken::class);
    }
}
