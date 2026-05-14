<?php

declare(strict_types=1);

namespace Capell\Api\Providers;

use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\LaravelPackageTools\Package;

final class ApiServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-api';

    public static string $packageName = 'capell-app/api';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile();

        if (file_exists(__DIR__ . '/../../routes/api.php')) {
            $package->hasRoute('api');
        }
    }

    public function packageBooted(): void
    {
        RateLimiter::for('capell-api', function (Request $request): Limit {
            $maxAttempts = config('capell-api.public_pages.rate_limit_per_minute', 60);
            $attempts = is_int($maxAttempts) ? $maxAttempts : 60;

            return Limit::perMinute($attempts)
                ->by((string) $request->ip());
        });
    }
}
