<?php

declare(strict_types=1);

use Capell\Core\Actions\RuntimeRefresh\RefreshInstalledPackageRuntimeAction;
use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Data\FoundationLayoutContainerPresentationData;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\LayoutBuilder\Actions\ResolveLayoutContainerPresentationAction;
use Capell\LayoutBuilder\Filament\Resources\LayoutBuilderResource;
use Capell\LayoutBuilder\Filament\Resources\LayoutPresets\LayoutPresetResource;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Capell\Tests\Support\InstalledRuntimeParity;
use Capell\Tests\Support\InstalledRuntimeState;
use Capell\Tests\Support\InstalledRuntimeTestCase;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Livewire\Livewire;

it('matches every fresh installed surface after metadata boot and repeated installation refresh', function (): void {
    $configure = require __DIR__ . '/../Integration/Providers/Fixtures/foundation-theme.php';
    $pageAliases = [];
    InstalledRuntimeParity::assertPackage('layout-builder', static function (Application $app) use (&$pageAliases): void {
        $views = $app->make(Factory::class);
        expect($views->exists('capell-layout-builder::components.layout-widgets.extension-gated'))->toBeTrue()
            ->and($views->exists('capell-theme-foundation::components.layout.main'))->toBeTrue();
        expect($app->make(Router::class)->getRoutes()->getByName('capell-layout-builder.fragments.show'))->not->toBeNull();
        if ($pageAliases === []) {
            foreach ([LayoutBuilderResource::class, LayoutPresetResource::class, WidgetResource::class] as $resource) {
                foreach ($resource::getPages() as $page) {
                    $component = Livewire::new($page->getPage());
                    expect($component::class)->toBe($page->getPage());
                    $pageAliases[$component->getName()] = $page->getPage();
                }
            }
        } else {
            foreach ($pageAliases as $alias => $class) {
                expect(Livewire::new($alias)::class)->toBe($class);
            }
        }
    }, $configure);
});

it('projects foundation container settings into public layout presentation', function (): void {
    InstalledRuntimeState::run(static function (): void {
        InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('theme-foundation'), false);
        $container = [
            'meta' => [
                'theme_settings' => [
                    'default' => ['surface_tone' => 'contrast'],
                    'inactive-theme' => ['surface_tone' => 'muted'],
                ],
            ],
        ];
        expect(ResolveLayoutContainerPresentationAction::run($container, 'default')->theme)->toBeNull();

        CapellCore::markPackageInstalled(FoundationThemeServiceProvider::$packageName);
        RefreshInstalledPackageRuntimeAction::run(CapellCore::getPackage(FoundationThemeServiceProvider::$packageName));
        $presentation = ResolveLayoutContainerPresentationAction::run($container, 'default');

        expect($presentation->theme)->toBeInstanceOf(FoundationLayoutContainerPresentationData::class)
            ->and($presentation->classes())->toContain('capell-container-surface-contrast')
            ->not->toContain('capell-container-surface-muted');
    });
});
