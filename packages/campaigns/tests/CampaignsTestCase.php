<?php

declare(strict_types=1);

namespace Capell\Campaigns\Tests;

use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Campaigns\Providers\AdminServiceProvider as CampaignsAdminServiceProvider;
use Capell\Campaigns\Providers\CampaignsServiceProvider;
use Capell\Campaigns\Providers\FrontendServiceProvider as CampaignsFrontendServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Forms\Providers\FormsServiceProvider;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Mosaic\Providers\MosaicServiceProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Override;

class CampaignsTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-campaigns';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AdminServiceProvider::class,
            FrontendServiceProvider::class,
            MosaicServiceProvider::class,
            FormsServiceProvider::class,
            ...$this->optionalAnalyticsProviders(),
            CampaignsServiceProvider::class,
            CampaignsAdminServiceProvider::class,
            CampaignsFrontendServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(MosaicServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FormsServiceProvider::$packageName);

        if (class_exists('Capell\\Analytics\\Providers\\AnalyticsServiceProvider')) {
            /** @var class-string $analyticsServiceProvider */
            $analyticsServiceProvider = 'Capell\\Analytics\\Providers\\AnalyticsServiceProvider';

            CapellCore::forcePackageInstalled($analyticsServiceProvider::$packageName);
        }

        CapellCore::forcePackageInstalled(CampaignsServiceProvider::$packageName);
    }

    /**
     * @return class-string[]
     */
    private function optionalAnalyticsProviders(): array
    {
        if (! class_exists('Capell\\Analytics\\Providers\\AnalyticsServiceProvider')) {
            return [];
        }

        return ['Capell\\Analytics\\Providers\\AnalyticsServiceProvider'];
    }
}
