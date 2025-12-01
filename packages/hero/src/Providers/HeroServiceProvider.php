<?php

declare(strict_types=1);

namespace Capell\Hero\Providers;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Packages\AbstractPackageServiceProvider;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Hero\Commands\DemoCommand;
use Capell\Hero\Commands\InstallCommand;
use Capell\Hero\Enums\ContentSchemaEnum;
use Capell\Hero\Enums\WidgetComponentEnum;
use Capell\Hero\Enums\WidgetSchemaEnum;
use Capell\Hero\Filament\Extenders\Page\HeroPageSchemaExtender;
use Capell\Layout\Enums\ComponentTypeEnum;
use Capell\Layout\Enums\SchemaTypeEnum;
use Capell\Layout\Providers\LayoutServiceProvider;
use Composer\InstalledVersions;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;

class HeroServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-hero';

    public static string $packageName = 'capell-app/hero';

    public static string $description = 'Hero section component for layout builder.';

    public function bootingPackage(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->registerAll();
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

        $this
            ->registerSchemas()
            ->registerPackageMetadata();
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::getPackage(static::$packageName)->isInstalled();
    }

    private function registerAll(): self
    {
        return $this
            ->registerComponents()
            ->registerSchemaExtenders()
            ->registerBladeComponents();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            path: __DIR__,
            sort: 10,
            description: static::getDescription(),
            installCommand: 'capell-hero:install',
            demoCommand: 'capell-hero:demo',
            demoParams: ['sites'],
            requirements: [
                AdminServiceProvider::$packageName,
                FrontendServiceProvider::$packageName,
                LayoutServiceProvider::$packageName,
            ],
            version: $this->getVersion(),
            url: 'https://capell.app',
        );

        return $this;
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

    private function registerComponents(): self
    {
        CapellCore::registerComponents(ComponentTypeEnum::Widget->value, WidgetComponentEnum::cases());

        return $this;
    }

    private function registerSchemas(): self
    {
        CapellAdmin::registerSchema(SchemaTypeEnum::Content, ContentSchemaEnum::Hero);
        CapellAdmin::registerSchema(SchemaTypeEnum::Widget, WidgetSchemaEnum::Hero);

        return $this;
    }

    private function registerSchemaExtenders(): self
    {
        $this->registerSchemaExtender(HeroPageSchemaExtender::TAG, HeroPageSchemaExtender::class);

        return $this;
    }

    private function registerBladeComponents(): self
    {
        Blade::componentNamespace('Capell\\Hero\\View\\Components', 'capell-hero');
        Blade::anonymousComponentNamespace('Capell\\Hero\\View\\Components');

        return $this;
    }

    private function registerSchemaExtender(string $tag, string $class): void
    {
        $this->app->singleton($class, fn (): object => new $class);

        $this->app->tag($class, $tag);
    }
}
