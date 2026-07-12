<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use BackedEnum;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\ConfiguratorTypeEnum as AdminConfiguratorTypeEnum;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Contracts\Extensions\ExtensionContribution;
use Capell\Core\Contracts\Extensions\RegistersExtensionAdminResource;
use Capell\Core\Contracts\Extensions\RegistersExtensionAsset;
use Capell\LayoutBuilder\Contracts\LayoutContentGroupContributor;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Configurators\Types\WidgetTypeConfigurator;
use Capell\LayoutBuilder\Filament\Extenders\Page\HeroPageSchemaExtender;
use Capell\LayoutBuilder\Filament\Resources\LayoutPresets\LayoutPresetResource;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Filament\Resources\Layouts\Schemas\Extenders\LayoutSchemaExtender;
use Capell\LayoutBuilder\Filament\Resources\Pages\Schemas\Extenders\PageSchemaExtender;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Support\Assets\FileVersionedCss;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Translation\FileLoader;
use Livewire\Livewire;
use RuntimeException;

final class LayoutBuilderAdminRegistrar implements ExtensionContribution, RegistersExtensionAdminResource, RegistersExtensionAsset
{
    public const string REGISTRATION_FLAG = 'capell.layout_builder.admin_registered';

    private const string LEGACY_LAYOUT_RESOURCE = 'Capell\\Admin\\LayoutBuilder\\Filament\\Resources\\Layouts\\LayoutResource';

    private const string LEGACY_WIDGET_RESOURCE = 'Capell\\Admin\\LayoutBuilder\\Filament\\Resources\\Widgets\\WidgetResource';

    private const string WIDGET_TYPE_CONFIGURATOR = WidgetTypeConfigurator::class;

    public function __construct(private readonly Container $app) {}

    public static function compatibleCapellApiVersion(): string
    {
        return '^1.0';
    }

    public function register(): void
    {
        if ($this->isRegistered() && $this->hasRegisteredAdminSurface()) {
            return;
        }

        $this->markRegistered();

        $this->app->tag([], LayoutContentGroupContributor::TAG);

        $this->registerViewAndTranslationNamespaces();
        $this->registerResources();
        $this->registerConfigurators();
        $this->registerSchemaExtenders();
        $this->registerLivewireComponents();
        $this->registerFilamentAssets();
    }

    public function isRegistered(): bool
    {
        return $this->app->bound(self::REGISTRATION_FLAG)
            && $this->app->make(self::REGISTRATION_FLAG) === true;
    }

    public function markRegistered(): void
    {
        $this->app->instance(self::REGISTRATION_FLAG, true);
    }

    public function registerResources(): void
    {
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
            class: WidgetResource::class,
            group: 'Widget',
        ));

        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
            class: LayoutResource::class,
            group: ResourceEnum::Layout->name,
        ));

        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
            class: LayoutPresetResource::class,
            group: ResourceEnum::Layout->name,
        ));
    }

    private function registerViewAndTranslationNamespaces(): void
    {
        $basePath = $this->packageBasePath();

        $this->app->make(ViewFactory::class)->replaceNamespace(
            'capell-layout-builder',
            $basePath . '/resources/views',
        );

        $this->app->afterResolving('translation.loader', function (mixed $translatorLoader) use ($basePath): void {
            if ($translatorLoader instanceof FileLoader) {
                $translatorLoader->addNamespace('capell-layout-builder', $basePath . '/resources/lang');
            }
        });
    }

    private function registerConfigurators(): void
    {
        foreach (ConfiguratorTypeEnum::getAllConfigurators() as $type => $configurators) {
            foreach ($configurators as $configurator) {
                $configuratorClass = $configurator instanceof BackedEnum ? $configurator->value : $configurator;

                if (! is_string($configuratorClass)) {
                    continue;
                }

                if (! class_exists($configuratorClass)) {
                    continue;
                }

                if (! method_exists($configuratorClass, 'getKey')) {
                    continue;
                }

                CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::configurator(
                    class: $configuratorClass,
                    group: $type,
                    name: $configuratorClass::getKey(),
                ));
            }
        }

        if (class_exists(self::WIDGET_TYPE_CONFIGURATOR)) {
            CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::configurator(
                class: self::WIDGET_TYPE_CONFIGURATOR,
                group: AdminConfiguratorTypeEnum::Blueprint->value,
                name: self::WIDGET_TYPE_CONFIGURATOR::getKey(),
            ));
        }
    }

    private function registerSchemaExtenders(): void
    {
        $this->registerSchemaExtender(SchemaExtenderEnum::Page->value, PageSchemaExtender::class);
        $this->registerSchemaExtender(SchemaExtenderEnum::Page->value, HeroPageSchemaExtender::class);
        $this->registerSchemaExtender(SchemaExtenderEnum::Layout->value, LayoutSchemaExtender::class);
    }

    private function registerSchemaExtender(string $tag, string $class): void
    {
        $alreadyTagged = collect($this->app->tagged($tag))
            ->contains(fn (object $extender): bool => $extender instanceof $class);

        if ($alreadyTagged) {
            return;
        }

        $this->app->singleton($class, fn (): object => new $class);
        $this->app->tag($class, $tag);
    }

    private function registerLivewireComponents(): void
    {
        $register = function (): void {
            Livewire::component('capell-layout-builder::filament.layout-builder', LayoutBuilder::class);

            if (! method_exists(Livewire::getFacadeRoot(), 'addNamespace')) {
                return;
            }

            Livewire::addNamespace(
                namespace: 'capell-layout-builder',
                classNamespace: 'Capell\\LayoutBuilder\\Livewire',
                viewPath: $this->packageBasePath() . '/resources/views/livewire',
                classPath: __DIR__ . '/../Livewire',
                classViewPath: $this->packageBasePath() . '/resources/views/livewire',
            );

        };

        if ($this->app->isBooted()) {
            $register();

            return;
        }

        $this->app->booted($register);
    }

    private function registerFilamentAssets(): void
    {
        $basePath = $this->packageBasePath();
        $publishDir = realpath($basePath . '/publishes');
        $cssSourcePath = $basePath . '/resources/css/layout-builder/admin/capell-layout-filament.css';
        $cssRelativePublicPath = 'css/capell-layout-builder/capell-layout-builder-filament.css';

        throw_if(in_array($publishDir, ['', '0', false], true), RuntimeException::class, 'Publish directory not found.');

        FilamentAsset::register(
            [
                FileVersionedCss::make('capell-layout-builder-filament', $cssSourcePath)
                    ->relativePublicPath($cssRelativePublicPath),
                AlpineComponent::make('layout-builder', $publishDir . '/build/js/components/layout-builder/admin/layout-builder.js')
                    ->loadedOnRequest(),
            ],
            package: 'capell-layout-builder',
        );
    }

    private function packageBasePath(): string
    {
        return dirname(__DIR__, 2);
    }

    private function hasRegisteredAdminSurface(): bool
    {
        if (! class_exists(LayoutResource::class) || ! class_exists(LayoutPresetResource::class) || ! class_exists(WidgetResource::class)) {
            return false;
        }

        $resources = CapellAdmin::getAdminSurfaceRegistry()->resources();

        return $this->containsResource($resources, LayoutResource::class, self::LEGACY_LAYOUT_RESOURCE)
            && $this->containsResource($resources, LayoutPresetResource::class)
            && $this->containsResource($resources, WidgetResource::class, self::LEGACY_WIDGET_RESOURCE);
    }

    /**
     * @param  array<int, class-string>  $resources
     */
    private function containsResource(array $resources, string ...$classes): bool
    {
        foreach ($classes as $class) {
            if (in_array($class, $resources, true)) {
                return true;
            }
        }

        return false;
    }
}
