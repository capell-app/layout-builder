<?php

declare(strict_types=1);

use Capell\Core\Events\PackageInstalled;
use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\Tests\Support\PackageInstallationSurfaceSnapshot;
use Capell\Tests\Support\PackageInstallationTestCase;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;

it('matches every fresh installed surface after metadata boot and repeated installation refresh', function (): void {
    $fresh = [];
    PackageInstallationTestCase::assertFreshInstalledBoot('layout-builder', static function (Application $app) use (&$fresh): void {
        $providers = $app->getLoadedProviders();
        expect($providers)->toHaveKey(LayoutBuilderServiceProvider::class)
            ->toHaveKey(FoundationThemeServiceProvider::class);
        $fresh = PackageInstallationSurfaceSnapshot::capture($app);
        expect($app->make(Router::class)->getRoutes()->getByName('capell-layout-builder.fragments.show'))->not->toBeNull();
    });

    PackageInstallationTestCase::assertInProcessInstallation('layout-builder', static function (Application $app, Closure $refresh) use ($fresh): void {
        $app->register(LayoutBuilderServiceProvider::class);
        expect($app->make(Router::class)->getRoutes()->getByName('capell-layout-builder.fragments.show'))->toBeNull();
        expect($app->make(Dispatcher::class)->getRawListeners())->not->toHaveKey('composing: capell-layout-builder::components.layout.container');
        $refresh();
        Event::dispatch(new PackageInstalled(CapellCore::getPackage(LayoutBuilderServiceProvider::$packageName)));
        $actual = PackageInstallationSurfaceSnapshot::capture($app);
        foreach ($fresh as $surface => $expected) {
            expect($actual[$surface])->toBe($expected, $surface);
        }
        $refresh();
        Event::dispatch(new PackageInstalled(CapellCore::getPackage(LayoutBuilderServiceProvider::$packageName)));
        $actual = PackageInstallationSurfaceSnapshot::capture($app);
        foreach ($fresh as $surface => $expected) {
            expect($actual[$surface])->toBe($expected, $surface);
        }
    });
});
