<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Providers;

use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

class ContentBlocksServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-content-blocks';

    public static string $packageName = 'capell-app/content-blocks';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name);
    }
}
