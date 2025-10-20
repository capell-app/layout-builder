<?php

declare(strict_types=1);

namespace Capell\Hero;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Packages\AbstractPackageServiceProvider;
use Capell\Hero\Commands\DemoCommand;
use Capell\Hero\Commands\InstallCommand;
use Capell\Hero\Enums\WidgetComponentEnum;
use Capell\Hero\Enums\WidgetSchemaEnum;
use Capell\Hero\Filament\Extenders\Page\HeroPageSchemaExtender;
use Capell\Layout\Enums\ComponentTypeEnum;
use Capell\Layout\Enums\SchemaTypeEnum;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;

class HeroServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-hero';

    public static string $description = 'Hero section component for layout builder.';

    public function bootingPackage(): void
    {
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
            self::$name,
            class: self::class,
            path: __DIR__,
            sort: 10,
            installCommand: true,
            demoCommand: true,
            demoParams: ['sites']
        );

        CapellCore::registerComponents(ComponentTypeEnum::Widget->value, WidgetComponentEnum::cases());

        CapellAdmin::registerSchemas(SchemaTypeEnum::Widget->value, WidgetSchemaEnum::cases());

        $this->registerSchemaExtender(HeroPageSchemaExtender::TAG, HeroPageSchemaExtender::class);
    }

    private function registerSchemaExtender(string $tag, string $class): void
    {
        $this->app->singleton($class, fn (): object => new $class);

        $this->app->tag($class, $tag);
    }
}
