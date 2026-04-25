<?php

declare(strict_types=1);

namespace Capell\Navigation\Providers;

use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Contracts\Navigation\DemoNavigationCreatorContract;
use Capell\Core\Contracts\Navigation\NavigationNamesResolver;
use Capell\Core\Contracts\Navigation\NavigationPageSyncer;
use Capell\Core\Exchanger\Enums\RelationOwnership;
use Capell\Core\Exchanger\Policy\OwnershipMap;
use Capell\Core\Models\Site;
use Capell\Navigation\Adapters\DemoNavigationCreatorAdapter;
use Capell\Navigation\Adapters\NavigationNamesResolverAdapter;
use Capell\Navigation\Adapters\NavigationPageSyncerAdapter;
use Capell\Navigation\Filament\Extenders\NavigationPageSchemaExtender;
use Capell\Navigation\Filament\Extenders\NavigationSiteExtender;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Policies\NavigationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class NavigationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerSchemaExtender(SchemaExtenderEnum::Page->value, NavigationPageSchemaExtender::class);
        $this->registerSchemaExtender(SchemaExtenderEnum::Site->value, NavigationSiteExtender::class);

        $this->app->singleton(NavigationPageSyncer::class, NavigationPageSyncerAdapter::class);
        $this->app->singleton(NavigationNamesResolver::class, NavigationNamesResolverAdapter::class);
        $this->app->singleton(DemoNavigationCreatorContract::class, DemoNavigationCreatorAdapter::class);
        $this->app->singleton(\Capell\Navigation\Support\NavigationNamesResolver::class, fn ($app) => new \Capell\Navigation\Support\NavigationNamesResolver($app['cache']));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell-navigation');

        Gate::policy(Navigation::class, NavigationPolicy::class);

        CapellAdmin::registerResource('Navigation', NavigationResource::class);

        OwnershipMap::register(Navigation::class, RelationOwnership::Shared);

        Site::resolveRelationUsing('navigations', fn (Site $site) => $site->hasMany(Navigation::class));
    }

    private function registerSchemaExtender(string $tag, string $class): void
    {
        $this->app->singleton($class, fn (): object => new $class);
        $this->app->tag($class, $tag);
    }
}
