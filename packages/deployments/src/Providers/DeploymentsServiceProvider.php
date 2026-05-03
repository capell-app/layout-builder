<?php

declare(strict_types=1);

namespace Capell\Deployments\Providers;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Deployments\Actions\PublishComposerRequirementAction;
use Capell\Deployments\Contracts\PublishesComposerChanges;
use Capell\Deployments\Data\ComposerRequirementData;
use Capell\Deployments\Data\PublishComposerChangeResultData;
use Capell\Deployments\Filament\Pages\DeploymentConnectionPage;
use Capell\Deployments\Models\DeploymentConnection;
use Composer\InstalledVersions;
use Spatie\LaravelPackageTools\Package;

class DeploymentsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-deployments';

    public static string $packageName = 'capell-app/deployments';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile()
            ->hasRoute('oauth')
            ->hasViews(self::$name)
            ->hasTranslations();
    }

    public function registeringPackage(): void
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            description: fn (): string => __('capell-deployments::package.description'),
        );

        $this->app->bind(PublishesComposerChanges::class, fn (): object => new class implements PublishesComposerChanges
        {
            public function publish(ComposerRequirementData $requirement): PublishComposerChangeResultData
            {
                $connection = DeploymentConnection::query()
                    ->where('is_active', true)
                    ->firstOrFail();

                return PublishComposerRequirementAction::run($requirement, $connection);
            }
        });

        if (config('capell-deployments.enabled', true)) {
            CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(DeploymentConnectionPage::class));
        }
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
}
