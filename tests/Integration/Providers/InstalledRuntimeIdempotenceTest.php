<?php

declare(strict_types=1);

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Actions\DisablePackageAction;
use Capell\Core\Actions\EnablePackageAction;
use Capell\Core\Actions\InstallPackageAction;
use Capell\Core\Actions\RuntimeRefresh\RefreshInstalledPackageRuntimeAction;
use Capell\Core\Facades\CapellCore;
use Capell\FrontendAuthoring\Enums\EditableRegionSurface;
use Capell\FrontendAuthoring\Support\EditorSurfaceRegistry;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Policies\LayoutPresetPolicy;
use Capell\LayoutBuilder\Support\FrontendAuthoring\LayoutBuilderEditorSurface;
use Capell\Tests\Support\InstalledRuntimeParity;
use Capell\Tests\Support\InstalledRuntimeState;
use Capell\Tests\Support\InstalledRuntimeTestCase;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

it('activates editor and console surfaces once while retaining the host widget policy', function (bool $installed): void {
    InstalledRuntimeState::run(static function () use ($installed): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('layout-builder'), $installed, static function (Application $app): void {
            $app->singleton(EditorSurfaceRegistry::class);
            $app->booting(static function (): void {
                Gate::policy(Widget::class, LayoutPresetPolicy::class);
            });
        }, sourceRoot: dirname(__DIR__, 3));
        $registry = $app->make(EditorSurfaceRegistry::class);
        $names = [
            'capell:layout-builder-widget-visual-regression',
            'capell:layout-builder-install',
            'capell:layouts:bulk-change',
            'capell:layout-builder:prune-bulk-change-runs',
            'capell:widget-snapshots:prune',
            'capell:layout-builder-screenshot-fixture',
        ];
        $commands = Artisan::all();
        if (! $installed) {
            expect(fn () => $registry->surface(EditableRegionSurface::LayoutBuilder))->toThrow(InvalidArgumentException::class);
            expect(array_intersect(array_keys($commands), $names))->toBe([]);
            InstalledRuntimeTestCase::prepareLateInstallMigrations($app);
            InstallPackageAction::run(CapellCore::getPackage('capell-app/layout-builder'));
            $commands = Artisan::all();
        }

        $surface = $registry->surface(EditableRegionSurface::LayoutBuilder);
        expect($surface)->toBeInstanceOf(LayoutBuilderEditorSurface::class)
            ->and(Gate::getPolicyFor(Widget::class))->toBeInstanceOf(LayoutPresetPolicy::class);
        foreach ($names as $name) {
            expect($commands)->toHaveKey($name);
        }

        $package = CapellCore::getPackage('capell-app/layout-builder');
        RefreshInstalledPackageRuntimeAction::run($package);
        RefreshInstalledPackageRuntimeAction::run($package);
        expect($registry->surface(EditableRegionSurface::LayoutBuilder))->toBe($surface)
            ->and(Gate::getPolicyFor(Widget::class))->toBeInstanceOf(LayoutPresetPolicy::class);
        $refreshed = Artisan::all();
        foreach ($names as $name) {
            expect($refreshed[$name])->toBe($commands[$name]);
        }
    });
})->with(['fresh' => true, 'late with resolved registry and console' => false]);

it('preserves ordered admin contributions across fresh boot, late install and repeated activation', function (): void {
    $fresh = InstalledRuntimeState::run(static function (): string {
        InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('layout-builder'), true, sourceRoot: dirname(__DIR__, 3));

        return serialize(CapellAdmin::getAdminSurfaceContributions());
    });

    InstalledRuntimeState::run(static function () use ($fresh): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('layout-builder'), false, sourceRoot: dirname(__DIR__, 3));
        $package = CapellCore::getPackage(LayoutBuilderServiceProvider::$packageName);
        InstalledRuntimeTestCase::prepareLateInstallMigrations($app);
        InstallPackageAction::run($package);
        expect(serialize(CapellAdmin::getAdminSurfaceContributions()))->toBe($fresh);

        for ($cycle = 0; $cycle < 2; $cycle++) {
            $provider = new LayoutBuilderServiceProvider($app);
            $provider->register();
            $provider->callBootedCallbacks();
            RefreshInstalledPackageRuntimeAction::run($package);
            RefreshInstalledPackageRuntimeAction::run($package);
            expect(serialize(CapellAdmin::getAdminSurfaceContributions()))->toBe($fresh);

            DisablePackageAction::run($package);
            expect(CapellCore::isPackageEnabled($package->name))->toBeFalse();
            EnablePackageAction::run($package);
            expect(CapellCore::isPackageEnabled($package->name))->toBeTrue()
                ->and(serialize(CapellAdmin::getAdminSurfaceContributions()))->toBe($fresh);
        }
    });
});

it('keeps one view namespace path through fresh boot, late installation and refresh', function (bool $installed): void {
    InstalledRuntimeState::run(static function () use ($installed): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('layout-builder'), $installed, sourceRoot: dirname(__DIR__, 3));
        $package = CapellCore::getPackage(LayoutBuilderServiceProvider::$packageName);
        if (! $installed) {
            InstalledRuntimeTestCase::prepareLateInstallMigrations($app);
            InstallPackageAction::run($package);
        }

        $finder = $app->make(Factory::class)->getFinder();
        expect($finder)->toBeInstanceOf(FileViewFinder::class);
        assert($finder instanceof FileViewFinder);
        $expected = [dirname(__DIR__, 3) . '/resources/views'];
        expect($finder->getHints()['capell-layout-builder'])->toBe($expected);
        RefreshInstalledPackageRuntimeAction::run($package);
        RefreshInstalledPackageRuntimeAction::run($package);
        expect($finder->getHints()['capell-layout-builder'])->toBe($expected);
    });
})->with(['fresh' => true, 'late' => false]);
