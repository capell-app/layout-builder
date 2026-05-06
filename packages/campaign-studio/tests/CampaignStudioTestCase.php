<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Tests;

use Capell\Admin\Providers\AdminServiceProvider;
use Capell\CampaignStudio\Providers\AdminServiceProvider as CampaignStudioAdminServiceProvider;
use Capell\CampaignStudio\Providers\CampaignStudioServiceProvider;
use Capell\CampaignStudio\Providers\FrontendServiceProvider as CampaignStudioFrontendServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\FormBuilder\Providers\FormBuilderServiceProvider;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Insights\Providers\InsightsServiceProvider;
use Capell\LayoutBuilder\Providers\LayoutBuilderServiceProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Override;

class CampaignStudioTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-campaign-studio';
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
            LayoutBuilderServiceProvider::class,
            FormBuilderServiceProvider::class,
            InsightsServiceProvider::class,
            CampaignStudioServiceProvider::class,
            CampaignStudioAdminServiceProvider::class,
            CampaignStudioFrontendServiceProvider::class,
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
        CapellCore::forcePackageInstalled(LayoutBuilderServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FormBuilderServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(InsightsServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(CampaignStudioServiceProvider::$packageName);
    }
}
