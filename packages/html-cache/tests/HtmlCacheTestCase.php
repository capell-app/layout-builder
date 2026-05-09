<?php

declare(strict_types=1);

namespace Capell\HtmlCache\Tests;

use Aimeos\Nestedset\NestedSetServiceProvider;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Macros\BlueprintMacros;
use Capell\Core\Models\Media;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\HtmlCache\Providers\HtmlCacheServiceProvider;
use Capell\Tests\AbstractTestCase;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Livewire\LivewireServiceProvider;
use MichalOravec\PaginateRoute\PaginateRouteServiceProvider;
use Override;

abstract class HtmlCacheTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-html-cache';
    }

    /**
     * @return array<int, class-string>
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            ActionsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FrontendServiceProvider::class,
            PaginateRouteServiceProvider::class,
            LivewireServiceProvider::class,
            HtmlCacheServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        Blueprint::mixin(new BlueprintMacros);
        (new NestedSetServiceProvider($app))->register();
        config(['activitylog.enabled' => false]);
        config(['media-library.media_model' => Media::class]);

        CapellCore::registerPackage(
            HtmlCacheServiceProvider::$packageName,
            path: realpath(__DIR__ . '/../'),
        );
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(HtmlCacheServiceProvider::$packageName);
    }
}
