<?php

declare(strict_types=1);

namespace Capell\Api\Providers;

use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

final class ApiServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-api';

    public static string $packageName = 'capell-app/api';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name);

        if (file_exists(__DIR__ . '/../../routes/api.php')) {
            $package->hasRoute('api');
        }
    }
}
