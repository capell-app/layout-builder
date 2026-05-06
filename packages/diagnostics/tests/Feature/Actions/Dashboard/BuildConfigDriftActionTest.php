<?php

declare(strict_types=1);

use Capell\Diagnostics\Actions\Dashboard\BuildConfigDriftAction;
use Capell\Diagnostics\Data\Dashboard\ConfigDriftEntryData;
use Composer\InstalledVersions;
use Illuminate\Support\Facades\File;

// ---------------------------------------------------------------------------
// Helper: subclass that overrides configPairs() to point at temp files.
// ---------------------------------------------------------------------------

function makeDriftAction(array $pairs): BuildConfigDriftAction
{
    return new class($pairs) extends BuildConfigDriftAction
    {
        /** @param list<array{0: string, 1: string, 2: string}> $pairs */
        public function __construct(private readonly array $pairs) {}

        /** @return list<array{0: string, 1: string, 2: string}> */
        protected function configPairs(): array
        {
            return $this->pairs;
        }
    };
}

function writeTempConfig(array $config): string
{
    $path = tempnam(sys_get_temp_dir(), 'capell_cfg_');
    assert($path !== false);
    file_put_contents($path, '<?php return ' . var_export($config, true) . ';');

    return $path;
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

it('dashboard-dashboard_reports no drift when shipped and host configs are identical', function (): void {
    $config = ['timeout' => 30, 'cache' => ['driver' => 'file', 'ttl' => 3600]];
    $shippedPath = writeTempConfig($config);
    $hostPath = writeTempConfig($config);

    try {
        $action = makeDriftAction([['core', $shippedPath, $hostPath]]);
        $result = $action->handle();

        expect($result->totalDriftCount)->toBe(0)
            ->and($result->packagesChecked)->toBe(1);
    } finally {
        @unlink($shippedPath);
        @unlink($hostPath);
    }
});

it('flags a missing key when host lacks a shipped key', function (): void {
    $shipped = ['foo' => ['bar' => ['baz' => 'value']]];
    $host = ['foo' => ['bar' => []]];

    $shippedPath = writeTempConfig($shipped);
    $hostPath = writeTempConfig($host);

    try {
        $action = makeDriftAction([['core', $shippedPath, $hostPath]]);
        $result = $action->handle();

        expect($result->totalDriftCount)->toBe(1);

        $entry = $result->entries->toCollection()->first();
        expect($entry)->not->toBeNull()
            ->and($entry->kind)->toBe('missing')
            ->and($entry->keyPath)->toBe('foo.bar.baz')
            ->and($entry->package)->toBe('core');
    } finally {
        @unlink($shippedPath);
        @unlink($hostPath);
    }
});

it('flags a stale key when host has a key no longer in shipped', function (): void {
    $shipped = ['foo' => ['bar' => []]];
    $host = ['foo' => ['bar' => ['legacy' => 'old']]];

    $shippedPath = writeTempConfig($shipped);
    $hostPath = writeTempConfig($host);

    try {
        $action = makeDriftAction([['core', $shippedPath, $hostPath]]);
        $result = $action->handle();

        expect($result->totalDriftCount)->toBe(1);

        $entry = $result->entries->toCollection()->first();
        expect($entry)->not->toBeNull()
            ->and($entry->kind)->toBe('stale')
            ->and($entry->keyPath)->toBe('foo.bar.legacy')
            ->and($entry->package)->toBe('core');
    } finally {
        @unlink($shippedPath);
        @unlink($hostPath);
    }
});

it('ignores value differences on matching keys', function (): void {
    $shipped = ['foo' => ['timeout' => 30]];
    $host = ['foo' => ['timeout' => 60]];

    $shippedPath = writeTempConfig($shipped);
    $hostPath = writeTempConfig($host);

    try {
        $action = makeDriftAction([['core', $shippedPath, $hostPath]]);
        $result = $action->handle();

        expect($result->totalDriftCount)->toBe(0);
    } finally {
        @unlink($shippedPath);
        @unlink($hostPath);
    }
});

it('skips packages whose host config is not published', function (): void {
    $shippedPath = writeTempConfig(['key' => 'value']);
    $hostPath = sys_get_temp_dir() . '/capell_nonexistent_' . uniqid() . '.php';

    try {
        $action = makeDriftAction([['core', $shippedPath, $hostPath]]);
        $result = $action->handle();

        expect($result->totalDriftCount)->toBe(0)
            ->and($result->packagesChecked)->toBe(0);
    } finally {
        @unlink($shippedPath);
    }
});

it('detects drift across multiple packages', function (): void {
    $coreShipped = ['debug' => false, 'newKey' => 'added'];
    $coreHost = ['debug' => false];

    $adminShipped = ['driver' => 'file'];
    $adminHost = ['driver' => 'file', 'staleKey' => 'dead'];

    $coreShippedPath = writeTempConfig($coreShipped);
    $coreHostPath = writeTempConfig($coreHost);
    $adminShippedPath = writeTempConfig($adminShipped);
    $adminHostPath = writeTempConfig($adminHost);

    try {
        $action = makeDriftAction([
            ['core', $coreShippedPath, $coreHostPath],
            ['admin', $adminShippedPath, $adminHostPath],
        ]);
        $result = $action->handle();

        expect($result->totalDriftCount)->toBe(2)
            ->and($result->packagesChecked)->toBe(2);

        $entries = $result->entries->toCollection();
        $missingEntry = $entries->first(fn (ConfigDriftEntryData $entry): bool => $entry->kind === 'missing');
        $staleEntry = $entries->first(fn (ConfigDriftEntryData $entry): bool => $entry->kind === 'stale');

        expect($missingEntry)->not->toBeNull()
            ->and($missingEntry->package)->toBe('core')
            ->and($missingEntry->keyPath)->toBe('newKey');

        expect($staleEntry)->not->toBeNull()
            ->and($staleEntry->package)->toBe('admin')
            ->and($staleEntry->keyPath)->toBe('staleKey');
    } finally {
        @unlink($coreShippedPath);
        @unlink($coreHostPath);
        @unlink($adminShippedPath);
        @unlink($adminHostPath);
    }
});

it('compares shipped config resolved from composer install paths', function (): void {
    if (! InstalledVersions::isInstalled('capell-app/core')) {
        $this->markTestSkipped('capell-app/core is not installed.');
    }

    $installPath = InstalledVersions::getInstallPath('capell-app/core');

    if (! is_string($installPath)) {
        $this->markTestSkipped('capell-app/core has no install path.');
    }

    $shippedPath = $installPath . '/config/capell.php';
    $hostPath = tempnam(sys_get_temp_dir(), 'capell_host_cfg_');
    assert($hostPath !== false);
    File::copy($shippedPath, $hostPath);

    try {
        $action = makeDriftAction([['core', $shippedPath, $hostPath]]);
        $result = $action->handle();

        expect($result->packagesChecked)->toBeGreaterThanOrEqual(1);
    } finally {
        File::delete($hostPath);
    }
});
