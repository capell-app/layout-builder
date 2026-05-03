<?php

declare(strict_types=1);

namespace Capell\Campaigns\Providers;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Campaigns\Enums\CampaignWidgetConfiguratorEnum;
use Capell\Campaigns\Enums\ResourceEnum;
use Capell\Campaigns\Filament\Widgets\CampaignOverviewStatsWidget;
use Capell\Campaigns\Filament\Widgets\TopCampaignsWidget;
use Capell\Campaigns\Filament\Widgets\TopLandingPagesWidget;
use Capell\Core\Facades\CapellCore;
use Capell\Mosaic\Enums\ConfiguratorTypeEnum as MosaicConfiguratorTypeEnum;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        $this
            ->registerResources()
            ->registerConfigurators()
            ->registerDashboardWidgets();
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(CampaignsServiceProvider::$packageName);
    }

    private function registerResources(): self
    {
        foreach (ResourceEnum::cases() as $resource) {
            CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
                class: $resource->value,
                group: $resource->name,
            ));
        }

        return $this;
    }

    private function registerConfigurators(): self
    {
        foreach (CampaignWidgetConfiguratorEnum::cases() as $configurator) {
            $configuratorClass = $configurator->value;

            CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::configurator(
                class: $configuratorClass,
                group: MosaicConfiguratorTypeEnum::Widget->value,
                name: $configuratorClass::getKey(),
            ));
        }

        return $this;
    }

    private function registerDashboardWidgets(): self
    {
        CapellAdmin::registerDashboardWidget(CampaignOverviewStatsWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(TopCampaignsWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(TopLandingPagesWidget::class, DashboardEnum::Main);

        return $this;
    }
}
