<?php

declare(strict_types=1);

it('returns mcp discovery details from the configured home route', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertJson([
            'name' => 'Capell MCP',
            'status' => 'ok',
            'servers' => [
                'knowledge' => 'http://localhost/mcp/capell/knowledge',
                'site' => 'http://localhost/mcp/capell/site',
            ],
        ]);
});
