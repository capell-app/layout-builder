<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Providers;

use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Enums\PresentationLoadingStrategy;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Frontend\Data\Assets\FrontendResourceData;
use Capell\Frontend\Data\Assets\FrontendResourceGroupData;
use Capell\Frontend\Data\Assets\PublicResourceSourceData;
use Capell\Frontend\Support\Assets\FrontendResourceRegistry;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionDefinitionData;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistrar;
use Spatie\LaravelPackageTools\Package;

abstract class AbstractFoundationWidgetServiceProvider extends AbstractPackageServiceProvider
{
    public static PackageTypeEnum $type = PackageTypeEnum::Package;

    abstract protected function definition(): WidgetExtensionDefinitionData;

    abstract protected static function resourceGroup(): string;

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)->hasViews()->hasTranslations()->hasAssets();
    }

    public function packageBooted(): void
    {
        resolve(WidgetExtensionRegistrar::class)->register($this->definition());

        resolve(FrontendResourceRegistry::class)->register(new FrontendResourceGroupData(
            key: static::resourceGroup(),
            label: static::$name,
            package: static::$packageName,
            resources: [
                FrontendResourceData::style(
                    handle: static::resourceGroup() . ':css',
                    package: static::$packageName,
                    source: new PublicResourceSourceData('vendor/' . static::$name . '/widget.css'),
                    loadingStrategy: PresentationLoadingStrategy::Visible,
                ),
                FrontendResourceData::classicScript(
                    handle: static::resourceGroup() . ':js',
                    package: static::$packageName,
                    source: new PublicResourceSourceData('vendor/' . static::$name . '/widget.js'),
                    loadingStrategy: static::scriptLoadingStrategy(),
                ),
            ],
        ));
    }

    protected static function scriptLoadingStrategy(): PresentationLoadingStrategy
    {
        return PresentationLoadingStrategy::Visible;
    }

    protected function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(static::$packageName);
    }
}
