<?php

declare(strict_types=1);

use Capell\AgentBridge\Actions\ConfirmAgentBridgeCapabilityAction;
use Capell\AgentBridge\Actions\CreateAgentBridgeTokenAction;
use Capell\AgentBridge\Actions\InvokeAgentBridgeCapabilityPreviewAction;
use Capell\AgentBridge\Data\AuthenticatedAgentBridgeClientData;
use Capell\AgentBridge\Data\CapabilityData;
use Capell\AgentBridge\Enums\CapabilityRiskEnum;
use Capell\AgentBridge\Enums\CapabilityServerEnum;
use Capell\AgentBridge\Models\CapellAgentBridgeConfirmation;
use Capell\AgentBridge\Support\CapellAgentBridgeCapabilityRegistry;
use Capell\AgentBridge\Tests\Fixtures\FakeCapabilityAction;
use Capell\AgentBridge\Tests\Fixtures\User;
use Illuminate\Auth\Access\AuthorizationException;

function registerFakeCapability(string $scope = 'capell.fake.write'): void
{
    resolve(CapellAgentBridgeCapabilityRegistry::class)->register(new CapabilityData(
        key: 'capell.fake.write',
        name: 'Fake write',
        description: 'Fake mutating capability.',
        scope: $scope,
        server: CapabilityServerEnum::Site,
        risk: CapabilityRiskEnum::High,
        actionClass: FakeCapabilityAction::class,
        auditEvent: 'capell_agent-bridge.fake.write',
    ));
}

it('previews and confirms a mutating capability with the same payload', function (): void {
    registerFakeCapability();

    $user = User::query()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'secret',
    ]);

    $created = CreateAgentBridgeTokenAction::run($user, 'Test client', ['capell.fake.write']);
    $client = new AuthenticatedAgentBridgeClientData(
        tokenId: (int) $created['token']->getKey(),
        name: 'Test client',
        scopes: ['capell.fake.write'],
    );

    $payload = ['name' => 'Example'];

    $preview = InvokeAgentBridgeCapabilityPreviewAction::run(
        capabilityKey: 'capell.fake.write',
        payload: $payload,
        client: $client,
        token: $created['token'],
        user: $user,
    );

    expect($preview['mode'])->toBe('preview')
        ->and($preview['confirmationToken'])->toBeString();

    $result = ConfirmAgentBridgeCapabilityAction::run(
        confirmationToken: $preview['confirmationToken'],
        payload: $payload,
        client: $client,
        token: $created['token'],
        user: $user,
    );

    expect($result['mode'])->toBe('confirmed')
        ->and($result['result']['message'])->toBe('Executed fake capability.')
        ->and(CapellAgentBridgeConfirmation::query()->whereNotNull('used_at')->count())->toBe(1);
});

it('rejects confirmation when the payload changes after preview', function (): void {
    registerFakeCapability();

    $user = User::query()->create([
        'name' => 'Test User',
        'email' => 'changed@example.com',
        'password' => 'secret',
    ]);

    $created = CreateAgentBridgeTokenAction::run($user, 'Test client', ['capell.fake.write']);
    $client = new AuthenticatedAgentBridgeClientData(
        tokenId: (int) $created['token']->getKey(),
        name: 'Test client',
        scopes: ['capell.fake.write'],
    );

    $preview = InvokeAgentBridgeCapabilityPreviewAction::run(
        capabilityKey: 'capell.fake.write',
        payload: ['name' => 'Original'],
        client: $client,
        token: $created['token'],
        user: $user,
    );

    ConfirmAgentBridgeCapabilityAction::run(
        confirmationToken: $preview['confirmationToken'],
        payload: ['name' => 'Changed'],
        client: $client,
        token: $created['token'],
        user: $user,
    );
})->throws(AuthorizationException::class, 'The Agent Bridge confirmation payload has changed.');
