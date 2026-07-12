<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Providers;

use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Enums\PresentationLoadingStrategy;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
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

        resolve(FrontendResourceRegistry::class)->group(static::resourceGroup())
            ->css('/vendor/' . static::$name . '/widget.css', loading: PresentationLoadingStrategy::Visible)
            ->js('/vendor/' . static::$name . '/widget.js', loading: static::scriptLoadingStrategy(), defer: true);
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
