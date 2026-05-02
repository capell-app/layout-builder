<?php

declare(strict_types=1);

namespace Capell\Campaigns\Providers;

use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Campaigns\Enums\CampaignWidgetConfiguratorEnum;
use Capell\Campaigns\Enums\ResourceEnum;
use Capell\Campaigns\Filament\Widgets\CampaignOverviewStatsWidget;
use Capell\Campaigns\Filament\Widgets\TopCampaignsWidget;
use Capell\Campaigns\Filament\Widgets\TopLandingPagesWidget;
use Capell\Mosaic\Enums\ConfiguratorTypeEnum as MosaicConfiguratorTypeEnum;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach (ResourceEnum::cases() as $resource) {
            CapellAdmin::registerResource($resource->name, class: $resource->value);
        }

        foreach (CampaignWidgetConfiguratorEnum::cases() as $configurator) {
            CapellAdmin::registerConfigurator(MosaicConfiguratorTypeEnum::Widget, $configurator->value);
        }

        CapellAdmin::registerDashboardWidget(CampaignOverviewStatsWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(TopCampaignsWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(TopLandingPagesWidget::class, DashboardEnum::Main);
    }
}
