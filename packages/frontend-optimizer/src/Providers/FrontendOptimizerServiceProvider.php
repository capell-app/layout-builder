<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\FrontendOptimizer\Contracts\CriticalCssGenerator;
use Capell\FrontendOptimizer\Support\LayoutAssetRegistry;
use Capell\FrontendOptimizer\Support\PlaywrightCriticalCssGenerator;
use Capell\FrontendOptimizer\Support\WidgetAssetRegistry;
use Composer\InstalledVersions;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;

final class FrontendOptimizerServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-frontend-optimizer';

    public static string $packageName = 'capell-app/frontend-optimizer';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-frontend-optimizer')
            ->hasMigration('2026_05_07_000001_create_frontend_optimizer_tables');
    }

    public function registeringPackage(): void
    {
        parent::registeringPackage();

        $this->app->singleton(LayoutAssetRegistry::class);
        $this->app->singleton(WidgetAssetRegistry::class);
        $this->app->singleton(CriticalCssGenerator::class, PlaywrightCriticalCssGenerator::class);

        Blade::directive('frontendOptimizerAssets', fn (string $expression): string => "<?php echo \\Capell\\FrontendOptimizer\\Actions\\RenderProfileAssetsAction::run({$expression}); ?>");

        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            description: fn (): string => 'Profile-based CSS and JavaScript delivery for public Capell pages.',
        );
    }

    private function getVersion(): string
    {
        if (! class_exists(InstalledVersions::class) || ! InstalledVersions::isInstalled(self::$packageName)) {
            return 'dev';
        }

        return InstalledVersions::getPrettyVersion(self::$packageName) ?? 'dev';
    }
}
