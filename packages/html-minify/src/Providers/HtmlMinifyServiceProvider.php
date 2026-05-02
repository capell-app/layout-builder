<?php

declare(strict_types=1);

namespace Capell\HtmlMinify\Providers;

use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Frontend\Contracts\HtmlMinifier as HtmlMinifierContract;
use Capell\HtmlMinify\Http\Middleware\HtmlMinifyMiddleware;
use Capell\HtmlMinify\Support\Html\HtmlMinifier;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;

final class HtmlMinifyServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'html-minify';

    public static string $packageName = 'capell-app/html-minify';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name);
    }

    public function registeringPackage(): void
    {
        parent::registeringPackage();

        $this->app->singleton(HtmlMinifierContract::class, HtmlMinifier::class);

        Route::aliasMiddleware('frontend.minify', HtmlMinifyMiddleware::class);
    }
}
