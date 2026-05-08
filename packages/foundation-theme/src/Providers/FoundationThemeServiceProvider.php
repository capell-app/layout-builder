<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Providers;

use Capell\Core\Actions\RegisterBlazeOptimizedViewsAction;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Events\PackageInstalled;
use Capell\Core\Events\PackageUninstalled;
use Capell\Core\Events\ThemeColorsUpdated;
use Capell\Core\Facades\CapellCore;
use Capell\Core\LayoutBuilder\Enums\FrontendComponentKeyEnum;
use Capell\Core\Models\Theme;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\FoundationTheme\Console\Commands\GenerateTailwindAssetsCommand;
use Capell\FoundationTheme\Enums\FoundationThemeAssetEnum;
use Capell\FoundationTheme\Filament\Settings\FoundationThemeSettingsSchema;
use Capell\FoundationTheme\Listeners\RegenerateTailwindAssetsOnThemeColorsUpdated;
use Capell\FoundationTheme\Listeners\RunTailwindAssetsOnPackageChange;
use Capell\FoundationTheme\Livewire\Assets\Table\PageAssets;
use Capell\FoundationTheme\Livewire\Widget\Pages;
use Capell\FoundationTheme\Settings\FoundationThemeSettings;
use Capell\FoundationTheme\Support\Blade\BladeDirectives;
use Capell\FoundationTheme\Support\Interceptors\Themes\FoundationThemeInterceptor;
use Capell\FoundationTheme\Support\Media\CapellUrlGenerator;
use Capell\FoundationTheme\Support\Tailwind\TailwindAssetsGenerator;
use Capell\FoundationTheme\View\Components\Media\Svg;
use Capell\FoundationTheme\View\Components\Widget\Page\Breadcrumbs as PageBreadcrumbsComponent;
use Capell\FoundationTheme\View\Components\Widget\Page\Children as PageChildrenComponent;
use Capell\FoundationTheme\View\Components\Widget\Page\Content as PageContentComponent;
use Capell\FoundationTheme\View\Components\Widget\Page\Latest as PageLatestComponent;
use Capell\FoundationTheme\View\Components\Widget\Page\Siblings as PageSiblingsComponent;
use Capell\FoundationTheme\View\Components\Widget\Slot as SlotComponent;
use Capell\Frontend\Contracts\AssetsRegistryInterface;
use Capell\Frontend\Contracts\FrontendComponentRegistryInterface;
use Capell\Frontend\Data\FrontendAssetData;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;

final class FoundationThemeServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-foundation-theme';

    public static string $packageName = 'capell-app/foundation-theme';

    public static PackageTypeEnum $type = PackageTypeEnum::Theme;

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile()
            ->hasViews('capell')
            ->hasCommands([GenerateTailwindAssetsCommand::class]);
    }

    public function packageBooted(): void
    {
        $this->registerBladeDirectives();
        $this->registerBladeComponents();
        $this->registerLayoutBuilderRendering();
        $this->registerMediaBladeComponents();

        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->registerAssets();
        $this->registerBlazeComponents();
        $this->registerTailwindEventListeners();
        $this->registerVendorNpmDependencies();
        $this->registerVendorCssJsAssets();
        $this->registerMediaUrlGenerator();
        $this->registerModelInterceptors();
        $this->registerSettingsSchemas();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton('capell.tailwind.generator', fn (): TailwindAssetsGenerator => new TailwindAssetsGenerator(
            $this->app->make(Filesystem::class),
        ));
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(self::$packageName);
    }

    private function registerAssets(): void
    {
        if (! $this->app->bound(AssetsRegistryInterface::class)) {
            return;
        }

        $registry = resolve(AssetsRegistryInterface::class);

        foreach (FoundationThemeAssetEnum::cases() as $asset) {
            $registry->registerAsset(
                $asset->getAsset(),
                new FrontendAssetData(component: $asset->getComponent()),
            );
        }
    }

    private function registerBladeDirectives(): void
    {
        BladeDirectives::register();
    }

    private function registerBlazeComponents(): void
    {
        RegisterBlazeOptimizedViewsAction::run(__DIR__ . '/../../resources/views/components');
    }

    private function registerTailwindEventListeners(): void
    {
        Event::listen(ThemeColorsUpdated::class, [RegenerateTailwindAssetsOnThemeColorsUpdated::class, 'handle']);
        Event::listen(PackageInstalled::class, [RunTailwindAssetsOnPackageChange::class, 'handleInstalled']);
        Event::listen(PackageUninstalled::class, [RunTailwindAssetsOnPackageChange::class, 'handleUninstalled']);
    }

    private function registerMediaUrlGenerator(): void
    {
        config(['media-library.url_generator' => CapellUrlGenerator::class]);
    }

    private function registerMediaBladeComponents(): void
    {
        Blade::component('capell::media.svg', Svg::class);
    }

    private function registerBladeComponents(): void
    {
        resolve(ViewFactory::class)->prependNamespace('capell', __DIR__ . '/../../resources/views');

        Blade::anonymousComponentPath(__DIR__ . '/../../resources/views/components', 'capell');
    }

    private function registerSettingsSchemas(): void
    {
        $registry = resolve(SettingsSchemaRegistry::class);
        $registry->registerSettingsClass('foundation_theme', FoundationThemeSettings::class);
        $registry->register('foundation_theme', FoundationThemeSettingsSchema::class);
    }

    private function registerModelInterceptors(): void
    {
        CapellCore::registerModelInterceptor(Theme::class, interceptorClass: FoundationThemeInterceptor::class);
    }

    private function registerVendorNpmDependencies(): void
    {
        $npmDependencies = config('capell-foundation-theme.npm_dependencies', []);

        if (! is_array($npmDependencies)) {
            return;
        }

        foreach ($npmDependencies as $package => $version) {
            if (! is_string($package)) {
                continue;
            }

            if ($package === '') {
                continue;
            }

            if (! is_string($version)) {
                continue;
            }

            if ($version === '') {
                continue;
            }

            CapellCore::registerVendorAsset(
                VendorAssetData::npmDependency($package, $version, self::$packageName),
            );
        }
    }

    private function registerVendorCssJsAssets(): void
    {
        CapellCore::registerVendorAsset(
            VendorAssetData::buildAsset(
                path: 'vendor/capell-frontend',
                file: 'resources/js/capell-frontend.js',
                packageName: self::$packageName,
            ),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('resources/css/foundation-theme.css', self::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindSource('resources/views/**/*.blade.php', self::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::buildAsset(
                path: 'vendor/capell-foundation-theme/layout-builder',
                file: 'resources/js/layout-builder/capell-layout-builder.js',
                packageName: self::$packageName,
            ),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('resources/css/layout-builder/capell-layout-builder.css', self::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('tippy.js/dist/tippy.css', self::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('swiper/css', self::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('swiper/css/autoplay', self::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('swiper/css/pagination', self::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('swiper/css/navigation', self::$packageName),
        );
    }

    private function registerLayoutBuilderRendering(): void
    {
        resolve(ViewFactory::class)->addNamespace(
            'capell-layout-builder',
            __DIR__ . '/../../resources/views/layout-builder',
        );

        Blade::componentNamespace('Capell\\FoundationTheme\\View\\Components', 'capell-layout-builder');
        Blade::component(PageBreadcrumbsComponent::class, 'capell-layout-builder::widget.page.breadcrumbs');
        Blade::component(PageContentComponent::class, 'capell-layout-builder-widget-page-content');
        Blade::component(SlotComponent::class, 'capell-layout-builder::widget.slot');
        Blade::component(PageChildrenComponent::class, 'capell-layout-builder::widget.page.children');
        Blade::component(PageContentComponent::class, 'capell-layout-builder::widget.page.content');
        Blade::component(PageLatestComponent::class, 'capell-layout-builder::widget.page.latest');
        Blade::component(PageSiblingsComponent::class, 'capell-layout-builder::widget.page.siblings');

        $registerLivewireComponents = function (): void {
            Livewire::addNamespace(
                namespace: 'capell-layout-builder',
                classNamespace: 'Capell\\FoundationTheme\\Livewire',
                viewPath: __DIR__ . '/../../resources/views/layout-builder/livewire',
                classPath: __DIR__ . '/../Livewire',
                classViewPath: __DIR__ . '/../../resources/views/layout-builder/livewire',
            );

            resolve('livewire.factory')->resolveMissingComponent(
                static fn (string $name): ?string => match ($name) {
                    'capell-layout-builder::assets.table.page-assets' => PageAssets::class,
                    'capell-layout-builder::widget.pages' => Pages::class,
                    default => null,
                },
            );
        };

        if ($this->app->isBooted()) {
            $registerLivewireComponents();
        } else {
            $this->app->booted($registerLivewireComponents);
        }

        $this->callAfterResolving(FrontendComponentRegistryInterface::class, function (FrontendComponentRegistryInterface $registry): void {
            $registry
                ->register(
                    key: FrontendComponentKeyEnum::SectionBlock->value,
                    component: 'capell-layout-builder::components.section.block',
                    aliases: [
                        'capell-content-sections::section.block',
                        'capell-layout-builder::section.block',
                    ],
                    props: [
                        'asset',
                        'class',
                        'color',
                        'icon',
                        'image',
                        'linkText',
                        'loop',
                        'meta',
                        'size',
                        'summary',
                        'tags',
                        'title',
                        'url',
                    ],
                )
                ->register(
                    key: FrontendComponentKeyEnum::SectionTeamMember->value,
                    component: 'capell-layout-builder::components.section.team-member',
                    aliases: [
                        'capell-content-sections::section.team-member',
                        'capell-layout-builder::section.team-member',
                    ],
                    props: [
                        'asset',
                        'class',
                        'color',
                        'icon',
                        'image',
                        'linkText',
                        'loop',
                        'meta',
                        'size',
                        'summary',
                        'title',
                        'url',
                    ],
                );
        });
    }
}
