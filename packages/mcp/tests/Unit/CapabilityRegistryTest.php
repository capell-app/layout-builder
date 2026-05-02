<?php

declare(strict_types=1);

use Capell\Mcp\Data\CapabilityData;
use Capell\Mcp\Enums\CapabilityRiskEnum;
use Capell\Mcp\Enums\CapabilityServerEnum;
use Capell\Mcp\Support\CapellMcpCapabilityRegistry;
use Capell\Mcp\Tests\Fixtures\FakeCapabilityAction;

it('filters capabilities by server and client scopes', function (): void {
    $registry = new CapellMcpCapabilityRegistry;

    $registry->register(new CapabilityData(
        key: 'capell.fake.read',
        name: 'Fake read',
        description: 'Fake read capability.',
        scope: 'capell.fake.read',
        server: CapabilityServerEnum::Site,
        risk: CapabilityRiskEnum::Read,
        actionClass: FakeCapabilityAction::class,
    ));

    $registry->register(new CapabilityData(
        key: 'capell.fake.write',
        name: 'Fake write',
        description: 'Fake write capability.',
        scope: 'capell.fake.write',
        server: CapabilityServerEnum::Site,
        risk: CapabilityRiskEnum::High,
        actionClass: FakeCapabilityAction::class,
    ));

    $visible = $registry->visibleFor(CapabilityServerEnum::Site, ['capell.fake.read']);

    expect($visible)->toHaveCount(1)
        ->and($visible->first()->key)->toBe('capell.fake.read');
});

it('prevents duplicate capability keys', function (): void {
    $registry = new CapellMcpCapabilityRegistry;
    $capability = new CapabilityData(
        key: 'capell.fake.read',
        name: 'Fake read',
        description: 'Fake read capability.',
        scope: 'capell.fake.read',
        server: CapabilityServerEnum::Site,
        risk: CapabilityRiskEnum::Read,
        actionClass: FakeCapabilityAction::class,
    );

    $registry->register($capability);

    expect(fn (): null => $registry->register($capability))
        ->toThrow(InvalidArgumentException::class);
});

it('hides required package capabilities when Capell core is unavailable', function (): void {
    $registry = new CapellMcpCapabilityRegistry;

    $registry->register(new CapabilityData(
        key: 'capell.fake.package',
        name: 'Fake package',
        description: 'Fake package capability.',
        scope: 'capell.fake.read',
        server: CapabilityServerEnum::Site,
        risk: CapabilityRiskEnum::Read,
        actionClass: FakeCapabilityAction::class,
        requiredPackage: 'capell-app/unknown-package',
    ));

    expect($registry->visibleFor(CapabilityServerEnum::Site, ['capell.fake.read']))->toHaveCount(0);
});
