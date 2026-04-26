<?php

declare(strict_types=1);

namespace Capell\Navigation\Providers;

use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Contracts\Navigation\NavigationNamesResolver;
use Capell\Core\Contracts\Navigation\NavigationPageSyncer;
use Capell\Core\Exchanger\Enums\RelationOwnership;
use Capell\Core\Exchanger\Policy\OwnershipMap;
use Capell\Core\Models\Site;
use Capell\Navigation\Adapters\NavigationNamesResolverAdapter;
use Capell\Navigation\Adapters\NavigationPageSyncerAdapter;
use Capell\Navigation\Console\Commands\DemoCommand;
use Capell\Navigation\Console\Commands\SetupCommand;
use Capell\Navigation\Filament\Extenders\NavigationPageSchemaExtender;
use Capell\Navigation\Filament\Extenders\NavigationSiteExtender;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Policies\NavigationPolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class NavigationServiceProvider extends ServiceProvider
{
    /** @var string */
    public static $packageName = 'capell-app/navigation';

    public function register(): void
    {
        $this->registerSchemaExtender(SchemaExtenderEnum::Page->value, NavigationPageSchemaExtender::class);
        $this->registerSchemaExtender(SchemaExtenderEnum::Site->value, NavigationSiteExtender::class);

        $this->app->singleton(NavigationPageSyncer::class, NavigationPageSyncerAdapter::class);
        $this->app->singleton(NavigationNamesResolver::class, NavigationNamesResolverAdapter::class);

        $this->commands([DemoCommand::class, SetupCommand::class]);
        $this->app->singleton(\Capell\Navigation\Support\NavigationNamesResolver::class, fn ($app): \Capell\Navigation\Support\NavigationNamesResolver => new \Capell\Navigation\Support\NavigationNamesResolver($app['cache']));
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

        CapellAdmin::registerResource('Navigation', NavigationResource::class);

        OwnershipMap::register(Navigation::class, RelationOwnership::Shared);

        Site::resolveRelationUsing('navigations', fn (Site $site): HasMany => $site->hasMany(Navigation::class));
    }

    private function registerSchemaExtender(string $tag, string $class): void
    {
        $this->app->singleton($class, fn (): object => new $class);
        $this->app->tag($class, $tag);
    }
}
