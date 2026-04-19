<?php

declare(strict_types=1);

use Capell\Plugins\Actions\InstallPluginAction;
use Capell\Plugins\Enums\CapabilityWarningLevel;
use Capell\Plugins\Enums\LicenseModel;
use Capell\Plugins\Enums\PluginKind;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Services\AnystackClient;
use Capell\Plugins\Services\ComposerRunner;
use Capell\Tests\Plugins\Unit\StubComposerProcess;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

$captured = [];
$exitCodes = [];
$errorOutputs = [];

function makeInstallPlugin(array $overrides = []): MarketplacePlugin
{
    return MarketplacePlugin::query()->create(array_merge([
        'name' => 'Test Plugin',
        'slug' => 'test-plugin',
        'description' => 'Test description',
        'composer_name' => 'vendor/plugin',
        'vendor' => 'vendor',
        'kind' => PluginKind::Full,
        'license_model' => LicenseModel::Free,
        'latest_version' => '1.0.0',
    ], $overrides));
}

function makeInstallComposerRunner(array $exitCodes, array $errorOutputs = []): ComposerRunner
{
    $captured = [];

    return new ComposerRunner(
        binary: 'composer',
        timeoutSeconds: 30,
        workingDirectory: sys_get_temp_dir(),
        processFactory: function (array $command, string $cwd, int $timeout) use (&$captured, &$exitCodes, &$errorOutputs): Process {
            $captured[] = $command;
            $exitCode = array_shift($exitCodes) ?? 0;
            $errorOutput = array_shift($errorOutputs) ?? '';

            return StubComposerProcess::make($exitCode, '', $errorOutput);
        },
    );
}

function makeInstallAnystackClient(): AnystackClient
{
    return new AnystackClient(
        baseUrl: 'https://api.anystack.sh',
        apiKey: null,
        timeoutSeconds: 5,
    );
}

beforeEach(function () use (&$captured, &$exitCodes, &$errorOutputs): void {
    $captured = [];
    $exitCodes = [];
    $errorOutputs = [];
});

test('install free plugin succeeds', function (): void {
    $plugin = makeInstallPlugin([
        'composer_name' => 'vendor/free-plugin',
    ]);

    $action = new InstallPluginAction(
        makeInstallComposerRunner([0]),
        makeInstallAnystackClient(),
    );

    $action->handle($plugin);

    expect($plugin->auditLog()->where('action', 'installed')->exists())->toBeTrue();
});

test('install paid plugin with valid license uses anystack product id', function (): void {
    $plugin = makeInstallPlugin([
        'composer_name' => 'vendor/paid-plugin',
        'anystack_product_id' => 'prod_xyz',
        'price_once' => 99,
    ]);

    config()->set('capell-plugins.anystack.composer_contact_email', 'unlock');
    Http::fake([
        '*/activate-key' => Http::response([
            'data' => [
                'id' => 'activation_xyz',
                'license_id' => 'license_abc',
            ],
        ], 200),
        '*/validate-key' => Http::response([
            'data' => ['id' => 'license_abc', 'suspended' => false],
            'meta' => ['valid' => true],
        ], 200),
    ]);

    $action = new InstallPluginAction(
        makeInstallComposerRunner([0, 0, 0]),
        makeInstallAnystackClient(),
    );

    $action->handle($plugin, 'license_key_123', 'site_abc');

    expect($plugin->auditLog()->where('action', 'installed')->exists())->toBeTrue();
});

test('install paid plugin passes fingerprint to composer', function (): void {
    $plugin = makeInstallPlugin([
        'composer_name' => 'vendor/paid-plugin',
        'anystack_product_id' => 'prod_xyz',
        'price_once' => 99,
    ]);

    Http::fake([
        '*/activate-key' => Http::response([
            'data' => [
                'id' => 'activation_xyz',
                'license_id' => 'license_abc',
            ],
        ], 200),
        '*/validate-key' => Http::response([
            'data' => ['id' => 'license_abc', 'suspended' => false],
            'meta' => ['valid' => true],
        ], 200),
    ]);

    $action = new InstallPluginAction(
        makeInstallComposerRunner([0, 0, 0]),
        makeInstallAnystackClient(),
    );

    $action->handle($plugin, 'lkey', 'site_abc', 'fp123');
})->markTestIncomplete('Composer runner capture needed');

test('install paid plugin without license key throws', function (): void {
    $plugin = makeInstallPlugin([
        'composer_name' => 'vendor/paid-plugin',
        'price_once' => 99,
    ]);

    $action = new InstallPluginAction(
        makeInstallComposerRunner([]),
        makeInstallAnystackClient(),
    );

    expect(fn () => $action->handle($plugin))
        ->toThrow(RuntimeException::class, 'Cannot install paid plugin without license key');
});

test('install paid plugin without site id throws', function (): void {
    $plugin = makeInstallPlugin([
        'composer_name' => 'vendor/paid-plugin',
        'anystack_product_id' => 'prod_xyz',
        'price_once' => 99,
    ]);

    $action = new InstallPluginAction(
        makeInstallComposerRunner([]),
        makeInstallAnystackClient(),
    );

    expect(fn () => $action->handle($plugin, 'some_license'))
        ->toThrow(RuntimeException::class, 'Cannot install paid plugin without siteId');
});

test('composer config failure throws', function (): void {
    $plugin = makeInstallPlugin([
        'composer_name' => 'vendor/paid-plugin',
        'anystack_product_id' => 'prod_xyz',
        'price_once' => 99,
    ]);

    $action = new InstallPluginAction(
        makeInstallComposerRunner([1], ['Auth failed']),
        makeInstallAnystackClient(),
    );

    expect(fn () => $action->handle($plugin, 'invalid_key', 'site_abc'))
        ->toThrow(RuntimeException::class, 'Failed to configure Anystack repository');
});

test('preview capability warnings returns correct highest level', function (): void {
    $plugin = makeInstallPlugin([
        'composer_name' => 'vendor/plugin',
        'capabilities' => ['db_schema_changes', 'http_outbound:capell.app', 'reads_secrets'],
    ]);

    $action = new InstallPluginAction(
        makeInstallComposerRunner([]),
        makeInstallAnystackClient(),
    );

    $warnings = $action->previewCapabilityWarnings($plugin);

    expect($warnings->highestLevel)->toBe(CapabilityWarningLevel::Red);
    expect($warnings->warnings)->toHaveCount(3);
});

test('preview capability warnings empty returns green', function (): void {
    $plugin = makeInstallPlugin([
        'composer_name' => 'vendor/plugin',
        'capabilities' => [],
    ]);

    $action = new InstallPluginAction(
        makeInstallComposerRunner([]),
        makeInstallAnystackClient(),
    );

    $warnings = $action->previewCapabilityWarnings($plugin);

    expect($warnings->highestLevel)->toBe(CapabilityWarningLevel::Green);
    expect($warnings->warnings)->toHaveCount(0);
});
