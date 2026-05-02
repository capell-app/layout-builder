<?php

declare(strict_types=1);

namespace Capell\Navigation\Providers;

use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Data\PageTypeData;
use Capell\Core\Events\SiteReplicated;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\Creator\DemoCreator;
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
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class NavigationServiceProvider extends ServiceProvider
{
    public static string $packageName = 'capell-app/navigation';

    public function register(): void
    {
        $this->registerPackageMetadata();
        $this->registerSchemaExtender(SchemaExtenderEnum::Page->value, NavigationPageSchemaExtender::class);
        $this->registerSchemaExtender(SchemaExtenderEnum::Site->value, NavigationSiteExtender::class);

        $this->app->singleton(NavigationPageSyncer::class, NavigationPageSyncerAdapter::class);
        $this->app->singleton(NavigationNamesResolver::class, NavigationNamesResolverAdapter::class);

        $this->commands([DemoCommand::class, SetupCommand::class]);
        $this->app->singleton(
            \Capell\Navigation\Support\NavigationNamesResolver::class,
            fn ($app): \Capell\Navigation\Support\NavigationNamesResolver => new \Capell\Navigation\Support\NavigationNamesResolver($app['cache']->store()),
        );

        CapellAdmin::registerResource('Navigation', NavigationResource::class);
        CapellCore::registerPageType(new PageTypeData(
            name: 'navigation',
            model: Navigation::class,
            label: 'Navigation',
        ));
        CapellCore::registerModels([Navigation::class]);

        foreach (NavigationConfiguratorTypeEnum::getAllConfigurators() as $type => $configurators) {
            CapellAdmin::registerConfigurators($type, $configurators, defaultConfigurators: true);
        }
    }

    public function boot(): void
    {
        // Skip auto-loading migrations during unit tests: the ordered migration workspace
        // (BuildsOrderedMigrationWorkspace) copies navigation's migrations into a temp
        // directory and calls loadMigrationsFrom() on that directory. Loading the same
        // migrations from two different paths would create the same tables twice.
        if (! $this->app->runningUnitTests()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        }

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell-navigation');

        Gate::policy(Navigation::class, NavigationPolicy::class);

        Site::resolveRelationUsing('navigations', fn (Site $site): HasMany => $site->hasMany(Navigation::class));

        Event::listen(SiteReplicated::class, ReplicateSiteNavigationsListener::class);

        $this->registerDemoCreatorMacros();
    }

    private function registerDemoCreatorMacros(): void
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
