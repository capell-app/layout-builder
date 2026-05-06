<?php

declare(strict_types=1);

it('does not expose agent-bridge discovery or knowledge routes by default', function (): void {
    $this->get('/')
        ->assertNotFound();

    $this->get('/agent-bridge/capell/knowledge')
        ->assertNotFound();
});

it('can return agent-bridge discovery details from a configured home route', function (): void {
    config()->set('capell-agent-bridge.routes.home', 'agent-bridge/capell/discover');
    config()->set('capell-agent-bridge.routes.knowledge', 'agent-bridge/capell/knowledge');
    config()->set('capell-agent-bridge.routes.site', 'agent-bridge/capell');

    require __DIR__ . '/../../routes/agent-bridge.php';

    $this->get('/agent-bridge/capell/discover')
        ->assertOk()
        ->assertJson([
            'name' => 'Capell Agent Bridge',
            'status' => 'ok',
            'servers' => [
                'knowledge' => 'http://localhost/agent-bridge/capell/knowledge',
                'site' => 'http://localhost/agent-bridge/capell',
            ],
        ]);
});
