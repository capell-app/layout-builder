<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Providers;

use Capell\ContentBlocks\Actions\RegisterBlockDefinitionProviderAction;
use Capell\ContentBlocks\Contracts\BlockDefinitionProvider;
use Capell\ContentBlocks\Support\BlockRegistry;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

final class ContentBlocksServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-content-blocks';

    public static string $packageName = 'capell-app/content-blocks';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(BlockRegistry::class);

        $this->callAfterResolving(BlockRegistry::class, function (BlockRegistry $registry): void {
            foreach ($this->app->tagged(BlockDefinitionProvider::TAG) as $provider) {
                if (! $provider instanceof BlockDefinitionProvider) {
                    continue;
                }

                RegisterBlockDefinitionProviderAction::run($registry, $provider);
            }
        });
    }
}
