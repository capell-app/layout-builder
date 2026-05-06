<?php

declare(strict_types=1);

namespace Capell\HtmlOptimizer\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Frontend\Contracts\HtmlMinifier as HtmlMinifierContract;
use Capell\HtmlOptimizer\Http\Middleware\HtmlOptimizerMiddleware;
use Capell\HtmlOptimizer\Support\Html\HtmlMinifier;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;

final class HtmlOptimizerServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'html-optimizer';

    public static string $packageName = 'capell-app/html-optimizer';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name);
    }

    public function registeringPackage(): void
    {
        parent::registeringPackage();

        $this->app->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this->app->singleton(HtmlMinifierContract::class, HtmlMinifier::class);

            Route::aliasMiddleware('frontend.minify', HtmlOptimizerMiddleware::class);
        });
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(self::$packageName);
    }
}
