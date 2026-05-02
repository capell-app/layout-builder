<?php

declare(strict_types=1);

use Capell\Mcp\Actions\ConfirmMcpCapabilityAction;
use Capell\Mcp\Actions\CreateMcpTokenAction;
use Capell\Mcp\Actions\InvokeMcpCapabilityPreviewAction;
use Capell\Mcp\Data\AuthenticatedMcpClientData;
use Capell\Mcp\Data\CapabilityData;
use Capell\Mcp\Enums\CapabilityRiskEnum;
use Capell\Mcp\Enums\CapabilityServerEnum;
use Capell\Mcp\Models\CapellMcpConfirmation;
use Capell\Mcp\Support\CapellMcpCapabilityRegistry;
use Capell\Mcp\Tests\Fixtures\FakeCapabilityAction;
use Capell\Mcp\Tests\Fixtures\User;
use Illuminate\Auth\Access\AuthorizationException;

function registerFakeCapability(string $scope = 'capell.fake.write'): void
{
    app(CapellMcpCapabilityRegistry::class)->register(new CapabilityData(
        key: 'capell.fake.write',
        name: 'Fake write',
        description: 'Fake mutating capability.',
        scope: $scope,
        server: CapabilityServerEnum::Site,
        risk: CapabilityRiskEnum::High,
        actionClass: FakeCapabilityAction::class,
        auditEvent: 'capell_mcp.fake.write',
    ));
}

it('previews and confirms a mutating capability with the same payload', function (): void {
    registerFakeCapability();

    $user = User::query()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'secret',
    ]);

    $created = CreateMcpTokenAction::run($user, 'Test client', ['capell.fake.write']);
    $client = new AuthenticatedMcpClientData(
        tokenId: (int) $created['token']->getKey(),
        name: 'Test client',
        scopes: ['capell.fake.write'],
    );

    $payload = ['name' => 'Example'];

    $preview = InvokeMcpCapabilityPreviewAction::run(
        capabilityKey: 'capell.fake.write',
        payload: $payload,
        client: $client,
        token: $created['token'],
        user: $user,
    );

    expect($preview['mode'])->toBe('preview')
        ->and($preview['confirmationToken'])->toBeString();

    $result = ConfirmMcpCapabilityAction::run(
        confirmationToken: $preview['confirmationToken'],
        payload: $payload,
        client: $client,
        token: $created['token'],
        user: $user,
    );

    expect($result['mode'])->toBe('confirmed')
        ->and($result['result']['message'])->toBe('Executed fake capability.')
        ->and(CapellMcpConfirmation::query()->whereNotNull('used_at')->count())->toBe(1);
});

it('rejects confirmation when the payload changes after preview', function (): void {
    registerFakeCapability();

    $user = User::query()->create([
        'name' => 'Test User',
        'email' => 'changed@example.com',
        'password' => 'secret',
    ]);

    $created = CreateMcpTokenAction::run($user, 'Test client', ['capell.fake.write']);
    $client = new AuthenticatedMcpClientData(
        tokenId: (int) $created['token']->getKey(),
        name: 'Test client',
        scopes: ['capell.fake.write'],
    );

    $preview = InvokeMcpCapabilityPreviewAction::run(
        capabilityKey: 'capell.fake.write',
        payload: ['name' => 'Original'],
        client: $client,
        token: $created['token'],
        user: $user,
    );

    ConfirmMcpCapabilityAction::run(
        confirmationToken: $preview['confirmationToken'],
        payload: ['name' => 'Changed'],
        client: $client,
        token: $created['token'],
        user: $user,
    );
})->throws(AuthorizationException::class, 'The MCP confirmation payload has changed.');
