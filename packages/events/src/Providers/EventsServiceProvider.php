<?php

declare(strict_types=1);

namespace Capell\Events\Providers;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\ResourceEnum as AdminResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Actions\RegisterBlazeOptimizedViewsAction;
use Capell\Core\Data\PageTypeData;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Events\Console\Commands\InstallCommand;
use Capell\Events\Enums\LivewireComponentEnum;
use Capell\Events\Enums\ResourceEnum;
use Capell\Events\Filament\Pages\EventCalendarPage;
use Capell\Events\Models\Event;
use Capell\Events\Support\EventModelRegistrar;
use Capell\Events\Support\RenderHooks\RegisterEventSchemaHooks;
use Capell\Events\Support\Schema\EventSchemaTemplate;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\PublishingStudio\WorkspaceRegistry;
use Capell\SeoSuite\Enums\SchemaTemplateTypeEnum;
use Capell\SeoSuite\Support\SchemaTemplates\SchemaTemplateRegistry;
use Composer\InstalledVersions;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;

class EventsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-events';

    public static string $packageName = 'capell-app/events';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasCommands([
                InstallCommand::class,
            ])
            ->hasMigrations([
                'create_event_venues_table',
                'create_events_table',
                'create_event_occurrences_table',
                'create_event_registrations_table',
                'create_event_notification_logs_table',
            ]);
    }

    public function registeringPackage(): void
    {
        $this->registerPackageMetadata();

        $this->app->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this->bootInstalledPackage();
        });
    }

    private function bootInstalledPackage(): self
    {
        return $this
            ->registerModels()
            ->registerAdminResources()
            ->registerPageTypes()
            ->registerPackageAssets()
            ->registerBladeComponents()
            ->registerBlazeComponents()
            ->registerLivewireComponents()
            ->registerRoutes()
            ->registerRenderHooks()
            ->registerSeoSchemaTemplate()
            ->registerPublishingStudio()
            ->registerAboutCommand();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            permissions: $this->getPackagePermissions(),
            description: fn (): string => __('capell-events::package.description'),
        );

        return $this;
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::getPackage(static::$packageName)->isInstalled();
    }

    private function getVersion(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return 'dev';
        }

        if (! InstalledVersions::isInstalled(static::$packageName)) {
            return 'dev';
        }

        return InstalledVersions::getPrettyVersion(static::$packageName) ?? 'dev';
    }

    /**
     * @return list<string>
     */
    private function getPackagePermissions(): array
    {
        return [
            'create_event',
            'create_event_occurrence',
            'create_event_registration',
            'create_event_venue',
            'delete_event',
            'delete_event_occurrence',
            'delete_event_registration',
            'delete_event_venue',
            'replicate_event',
            'restore_event',
            'restore_event_venue',
            'update_event',
            'update_event_occurrence',
            'update_event_registration',
            'update_event_venue',
            'view_any_event',
            'view_any_event_occurrence',
            'view_any_event_registration',
            'view_any_event_venue',
            'view_event',
            'view_event_occurrence',
            'view_event_registration',
            'view_event_venue',
        ];
    }

    private function registerModels(): self
    {
        EventModelRegistrar::register();

        return $this;
    }

    private function registerAdminResources(): self
    {
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(EventCalendarPage::class));

        foreach (ResourceEnum::cases() as $resourceEnum) {
            CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
                class: $resourceEnum->value,
                group: $resourceEnum === ResourceEnum::Event ? AdminResourceEnum::Page->name : $resourceEnum->name,
                name: strtolower($resourceEnum->name),
            ));
        }

        return $this;
    }

    private function registerPageTypes(): self
    {
        CapellCore::registerPageType(
            new PageTypeData(
                name: 'event',
                model: Event::class,
                label: fn (): string => __('capell-events::generic.event'),
            ),
        );

        return $this;
    }

    private function registerPackageAssets(): self
    {
        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindSource('resources/views/**/*.blade.php', static::$packageName),
        );

        return $this;
    }

    private function registerBladeComponents(): self
    {
        Blade::componentNamespace('Capell\\Events\\View\\Components', 'capell-events');
        Blade::anonymousComponentNamespace('Capell\\Events\\View\\Components');

        return $this;
    }

    private function registerBlazeComponents(): self
    {
        RegisterBlazeOptimizedViewsAction::run(__DIR__ . '/../../resources/views');

        return $this;
    }

    private function registerLivewireComponents(): self
    {
        if ($this->isLivewireV3()) {
            foreach (LivewireComponentEnum::getComponents() as $name => $component) {
                if (! class_exists($component)) {
                    continue;
                }

                Livewire::component($name, $component);
            }
        } else {
            Livewire::addNamespace(
                namespace: 'capell-events',
                classNamespace: 'Capell\\Events\\Livewire',
                classPath: __DIR__ . '/../Livewire',
                classViewPath: __DIR__ . '/../../resources/views/livewire',
            );
        }

        return $this;
    }

    private function isLivewireV3(): bool
    {
        if (! class_exists(InstalledVersions::class) || ! InstalledVersions::isInstalled('livewire/livewire')) {
            return true;
        }

        $version = InstalledVersions::getVersion('livewire/livewire');

        return version_compare($version, '4.0.0', '<');
    }

    private function registerRoutes(): self
    {
        Route::middleware(['web', 'frontend.resolve'])
            ->name('capell-events.')
            ->group(__DIR__ . '/../../routes/web.php');

        return $this;
    }

    private function registerRenderHooks(): self
    {
        if (class_exists(RenderHookRegistry::class)) {
            $this->app->make(RegisterEventSchemaHooks::class)->register();
        }

        return $this;
    }

    private function registerSeoSchemaTemplate(): self
    {
        if (! class_exists(SchemaTemplateRegistry::class)) {
            return $this;
        }

        if (! class_exists(SchemaTemplateTypeEnum::class)) {
            return $this;
        }

        $registry = $this->app->make(SchemaTemplateRegistry::class);
        $registry->registerIfMissing(
            SchemaTemplateTypeEnum::Event,
            new EventSchemaTemplate,
        );

        return $this;
    }

    private function registerPublishingStudio(): self
    {
        WorkspaceRegistry::register(Event::class);

        return $this;
    }

    private function registerAboutCommand(): self
    {
        if ($this->app->runningInConsole() && class_exists(AboutCommand::class)) {
            AboutCommand::add('Capell', [
                self::$name => fn (): string => $this->getVersion(),
            ]);
        }

        return $this;
    }
}
