<?php

declare(strict_types=1);

namespace Capell\Navigation\Providers;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Data\PageTypeData;
use Capell\Core\Events\PageUrlChanged;
use Capell\Core\Events\SiteReplicated;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\Creator\DemoCreator;
use Capell\Frontend\Enums\CacheEnum as FrontendCacheEnum;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Adapters\NavigationNamesResolverAdapter;
use Capell\Navigation\Adapters\NavigationPageSyncerAdapter;
use Capell\Navigation\Console\Commands\DemoCommand;
use Capell\Navigation\Console\Commands\SetupCommand;
use Capell\Navigation\Contracts\NavigationNamesResolver;
use Capell\Navigation\Contracts\NavigationPageSyncer;
use Capell\Navigation\Enums\NavigationConfiguratorTypeEnum;
use Capell\Navigation\Filament\Extenders\NavigationPageSchemaExtender;
use Capell\Navigation\Filament\Extenders\NavigationSiteExtender;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Capell\Navigation\Listeners\ReplicateSiteNavigationsListener;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Policies\NavigationPolicy;
use Capell\Navigation\Support\Creator\NavigationDemoCreator;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class NavigationServiceProvider extends ServiceProvider
{
    public static string $packageName = 'capell-app/navigation';

    public function register(): void
    {
        $this->registerPackageMetadata();
        $this->commands([DemoCommand::class, SetupCommand::class]);
    }

    public function boot(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        $this
            ->registerServices()
            ->registerSchemaExtenders()
            ->registerResources()
            ->registerPageTypes()
            ->registerModels()
            ->registerConfigurators()
            ->registerPackageAssets()
            ->registerBladeComponents()
            ->registerPolicies()
            ->registerRelationships()
            ->registerEventListeners()
            ->registerDemoCreatorMacros();
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(static::$packageName);
    }

    private function registerServices(): self
    {
        $this->app->singleton(NavigationPageSyncer::class, NavigationPageSyncerAdapter::class);
        $this->app->singleton(NavigationNamesResolver::class, NavigationNamesResolverAdapter::class);
        $this->app->singleton(
            \Capell\Navigation\Support\NavigationNamesResolver::class,
            fn ($app): \Capell\Navigation\Support\NavigationNamesResolver => new \Capell\Navigation\Support\NavigationNamesResolver($app['cache']->store()),
        );

        return $this;
    }

    private function registerSchemaExtenders(): self
    {
        $this->registerSchemaExtender(SchemaExtenderEnum::Page->value, NavigationPageSchemaExtender::class);
        $this->registerSchemaExtender(SchemaExtenderEnum::Site->value, NavigationSiteExtender::class);

        return $this;
    }

    private function registerResources(): self
    {
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
            class: NavigationResource::class,
            group: 'Navigation',
        ));

        return $this;
    }

    private function registerPageTypes(): self
    {
        CapellCore::registerPageType(new PageTypeData(
            name: 'navigation',
            model: Navigation::class,
            label: 'Navigation',
        ));

        return $this;
    }

    private function registerModels(): self
    {
        CapellCore::registerModels([Navigation::class]);

        return $this;
    }

    private function registerConfigurators(): self
    {
        foreach (NavigationConfiguratorTypeEnum::getAllConfigurators() as $type => $configurators) {
            foreach ($configurators as $configuratorClass) {
                CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::configurator(
                    class: $configuratorClass,
                    group: $type,
                    name: $configuratorClass::getKey(),
                ));
            }
        }

        return $this;
    }

    private function registerPackageAssets(): self
    {
        // Skip auto-loading migrations during unit tests: the ordered migration workspace
        // (BuildsOrderedMigrationWorkspace) copies navigation's migrations into a temp
        // directory and calls loadMigrationsFrom() on that directory. Loading the same
        // migrations from two different paths would create the same tables twice.
        if (! $this->app->runningUnitTests()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        }

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell-navigation');

        return $this;
    }

    private function registerBladeComponents(): self
    {
        Blade::componentNamespace('Capell\\Navigation\\View\\Components', 'capell-navigation');

        return $this;
    }

    private function registerPolicies(): self
    {
        Gate::policy(Navigation::class, NavigationPolicy::class);

        return $this;
    }

    private function registerRelationships(): self
    {
        Site::resolveRelationUsing('navigations', fn (Site $site): HasMany => $site->hasMany(Navigation::class));

        return $this;
    }

    private function registerEventListeners(): self
    {
        Event::listen(SiteReplicated::class, ReplicateSiteNavigationsListener::class);
        Event::listen(PageUrlChanged::class, $this->handlePageUrlChanged(...));

        return $this;
    }

    private function handlePageUrlChanged(PageUrlChanged $event): void
    {
        BuildNavigationRenderModelAction::flushPageCache();

        CapellCore::removeCacheKey(FrontendCacheEnum::Navigations->value);
        CapellCore::removeCacheKey(FrontendCacheEnum::siteNavigations($event->site_id));

        $navigations = Navigation::query()
            ->where(function ($query) use ($event): void {
                $query
                    ->where('site_id', $event->site_id)
                    ->orWhereNull('site_id');
            })
            ->where(function ($query) use ($event): void {
                $query
                    ->where('language_id', $event->language_id)
                    ->orWhereNull('language_id');
            })
            ->get(['id', 'key', 'site_id', 'language_id']);

        foreach ($navigations as $navigation) {
            CapellCore::removeCacheKey(FrontendCacheEnum::navigationById((int) $navigation->getKey()));

            if (is_numeric($navigation->site_id)) {
                CapellCore::removeCacheKey(FrontendCacheEnum::navigation(
                    $navigation->key,
                    (int) $navigation->site_id,
                    is_numeric($navigation->language_id) ? (int) $navigation->language_id : null,
                ));
            }
        }
    }

    private function registerDemoCreatorMacros(): self
    {
        DemoCreator::macro('setupMainNavigation', function (Site $site, Language $language, Page $home): void {
            resolve(NavigationDemoCreator::class)->setupMainNavigation($site, $language, $home);
        });

        DemoCreator::macro('setupFooterNavigation', function (Site $site, Language $language): void {
            resolve(NavigationDemoCreator::class)->setupFooterNavigation($site, $language);
        });

        DemoCreator::macro('subFooterNavigation', function (Site $site, ?Language $language): void {
            resolve(NavigationDemoCreator::class)->setupSubFooterNavigation($site, $language);
        });

        return $this;
    }

    private function registerSchemaExtender(string $tag, string $class): void
    {
        $this->app->singleton($class, fn (): object => new $class);
        $this->app->tag($class, $tag);
    }

    private function registerPackageMetadata(): void
    {
        CapellCore::registerPackage(
            static::$packageName,
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(static::$packageName),
            description: 'Navigation for Capell',
        );
    }
}
