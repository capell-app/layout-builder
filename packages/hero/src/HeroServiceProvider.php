<?php

declare(strict_types=1);

namespace Capell\Hero;

use Capell\Admin\AdminServiceProvider;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Packages\AbstractPackageServiceProvider;
use Capell\Frontend\FrontendServiceProvider;
use Capell\Hero\Commands\DemoCommand;
use Capell\Hero\Commands\InstallCommand;
use Capell\Hero\Enums\ContentSchemaEnum;
use Capell\Hero\Enums\WidgetComponentEnum;
use Capell\Hero\Enums\WidgetSchemaEnum;
use Capell\Hero\Filament\Extenders\Page\HeroPageSchemaExtender;
use Capell\Layout\Enums\ComponentTypeEnum;
use Capell\Layout\Enums\SchemaTypeEnum;
use Capell\Layout\LayoutServiceProvider;
use Composer\InstalledVersions;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelPackageTools\Package;

class HeroServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-hero';

    public static string $packageName = 'capell-app/hero';

    public static string $description = 'Hero section component for layout builder.';

    public function bootingPackage(): void
    {
        if (CapellCore::getPackage(static::$packageName)->isInstalled() !== true) {
            return;
        }

        CapellCore::registerComponents(ComponentTypeEnum::Widget->value, WidgetComponentEnum::cases());

        foreach (SchemaTypeEnum::getAllSchemas() as $type => $schemas) {
            CapellAdmin::registerSchemas($type, $schemas, defaultSchemas: true);
        }

        $this->registerSchemaExtender(HeroPageSchemaExtender::TAG, HeroPageSchemaExtender::class);

        Blade::componentNamespace('Capell\\Hero\\View\\Components', 'capell-hero');
        Blade::anonymousComponentNamespace('Capell\\Hero\\View\\Components');
    }

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasViews(self::$name)
            ->hasCommands([
                DemoCommand::class,
                InstallCommand::class,
            ])
            ->hasTranslations();
    }

    public function registeringPackage(): void
    {
        parent::registeringPackage();

        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            description: static::getDescription(),
            path: __DIR__,
            sort: 10,
            installCommand: 'capell-hero:install',
            demoCommand: 'capell-hero:demo',
            demoParams: ['sites'],
            requirements: [
                AdminServiceProvider::$packageName,
                FrontendServiceProvider::$packageName,
                LayoutServiceProvider::$packageName,
            ],
            version: InstalledVersions::getPrettyVersion(static::$packageName),
            url: 'https://capell.app',
        );
    }

    private function registerSchemaExtender(string $tag, string $class): void
    {
        $this->app->singleton($class, fn (): object => new $class);

        $this->app->tag($class, $tag);
    }
}
