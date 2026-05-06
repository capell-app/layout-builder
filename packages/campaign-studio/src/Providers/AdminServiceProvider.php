<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Providers;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\CampaignStudio\Enums\CampaignWidgetConfiguratorEnum;
use Capell\CampaignStudio\Enums\ResourceEnum;
use Capell\CampaignStudio\Filament\Widgets\CampaignOverviewStatsWidget;
use Capell\CampaignStudio\Filament\Widgets\TopCampaignStudioWidget;
use Capell\CampaignStudio\Filament\Widgets\TopLandingPagesWidget;
use Capell\Core\Facades\CapellCore;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum as LayoutBuilderConfiguratorTypeEnum;
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
        return CapellCore::isPackageInstalled(CampaignStudioServiceProvider::$packageName);
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
                group: LayoutBuilderConfiguratorTypeEnum::Widget->value,
                name: $configuratorClass::getKey(),
            ));
        }

        return $this;
    }

    private function registerDashboardWidgets(): self
    {
        CapellAdmin::registerDashboardWidget(CampaignOverviewStatsWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(TopCampaignStudioWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(TopLandingPagesWidget::class, DashboardEnum::Main);

        return $this;
    }
}
