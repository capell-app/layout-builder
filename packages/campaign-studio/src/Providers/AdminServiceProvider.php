<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Providers;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Enums\NavigationGroupPositionEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\CampaignStudio\Actions\BuildCampaignOverviewStatsAction;
use Capell\CampaignStudio\Enums\CampaignWidgetConfiguratorEnum;
use Capell\CampaignStudio\Enums\ResourceEnum;
use Capell\CampaignStudio\Filament\Widgets\TopCampaignStudioWidget;
use Capell\CampaignStudio\Filament\Widgets\TopLandingPagesWidget;
use Capell\Core\Facades\CapellCore;
use Capell\Core\LayoutBuilder\Enums\ConfiguratorTypeEnum as LayoutBuilderConfiguratorTypeEnum;
use Carbon\CarbonImmutable;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->booting(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this
                ->registerNavigationGroups()
                ->registerResources()
                ->registerConfigurators();
        });
    }

    public function boot(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        $this
            ->registerOverviewStats()
            ->registerDashboardWidgets();
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(CampaignStudioServiceProvider::$packageName);
    }

    private function registerNavigationGroups(): self
    {
        CapellAdmin::registerNavigationGroup(
            label: 'capell-admin::navigation.group_marketing',
            position: NavigationGroupPositionEnum::After,
            relativeTo: 'capell-admin::navigation.group_content',
        );

        return $this;
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
        CapellAdmin::registerDashboardWidget(TopCampaignStudioWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(TopLandingPagesWidget::class, DashboardEnum::Main);

        return $this;
    }

    private function registerOverviewStats(): self
    {
        CapellAdmin::registerOverviewStat(
            key: 'campaign_overview',
            label: fn (): string => __('capell-campaign-studio::widgets.active_campaign-studio'),
            value: fn (): int => $this->campaignOverview()['active_campaign-studio'],
            group: fn (): string => __('capell-admin::navigation.group_marketing'),
            sort: 150,
            settingsLabel: fn (): string => __('capell-campaign-studio::widgets.campaign_overview'),
        );

        CapellAdmin::registerOverviewStat(
            key: 'campaign_overview.conversions',
            label: fn (): string => __('capell-campaign-studio::widgets.conversions'),
            value: fn (): int => $this->campaignOverview()['conversions'],
            group: fn (): string => __('capell-admin::navigation.group_marketing'),
            sort: 151,
            settingsKey: 'campaign_overview',
            settingsLabel: fn (): string => __('capell-campaign-studio::widgets.campaign_overview'),
        );

        CapellAdmin::registerOverviewStat(
            key: 'campaign_overview.conversion_rate',
            label: fn (): string => __('capell-campaign-studio::widgets.conversion_rate'),
            value: fn (): string => $this->campaignOverview()['conversion_rate'] . '%',
            group: fn (): string => __('capell-admin::navigation.group_marketing'),
            sort: 152,
            settingsKey: 'campaign_overview',
            settingsLabel: fn (): string => __('capell-campaign-studio::widgets.campaign_overview'),
        );

        return $this;
    }

    /**
     * @return array{active_campaign-studio: int, conversions: int, conversion_rate: int|float|string}
     */
    private function campaignOverview(): array
    {
        static $overview = null;

        if (is_array($overview)) {
            return $overview;
        }

        $now = CarbonImmutable::now();
        $rangeStart = $now->startOfWeek();
        $rangeEnd = $now->endOfWeek();
        $overview = BuildCampaignOverviewStatsAction::run($rangeStart, $rangeEnd);

        return $overview;
    }
}
