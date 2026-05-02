<?php

declare(strict_types=1);

it('does not expose mcp discovery or knowledge routes by default', function (): void {
    $this->get('/')
        ->assertNotFound();

    $this->get('/mcp/capell/knowledge')
        ->assertNotFound();
});

it('can return mcp discovery details from a configured home route', function (): void {
    config()->set('capell-mcp.routes.home', 'mcp/capell/discover');
    config()->set('capell-mcp.routes.knowledge', 'mcp/capell/knowledge');
    config()->set('capell-mcp.routes.site', 'mcp/capell');

    require __DIR__ . '/../../routes/mcp.php';

    $this->get('/mcp/capell/discover')
        ->assertOk()
        ->assertJson([
            'name' => 'Capell MCP',
            'status' => 'ok',
            'servers' => [
                'knowledge' => 'http://localhost/mcp/capell/knowledge',
                'site' => 'http://localhost/mcp/capell',
            ],
        ]);
});
