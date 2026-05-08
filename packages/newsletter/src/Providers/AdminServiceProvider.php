<?php

declare(strict_types=1);

namespace Capell\Newsletter\Providers;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\NavigationGroupPositionEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Support\SiteScope;
use Capell\Core\Facades\CapellCore;
use Capell\Newsletter\Console\Commands\RequeueDueProviderSyncAttemptsCommand;
use Capell\Newsletter\Enums\ResourceEnum;
use Capell\Newsletter\Enums\SubscriberStatus;
use Capell\Newsletter\Enums\SyncStatus;
use Capell\Newsletter\Models\Subscriber;
use Capell\Newsletter\Models\SyncAttempt;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->booting(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this
                ->registerNavigationGroups()
                ->registerResources();
        });
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'capell-newsletter');

        if (! $this->isPackageInstalled()) {
            return;
        }

        if ($this->app->runningInConsole()) {
            $this->commands([RequeueDueProviderSyncAttemptsCommand::class]);
        }

        $this->registerOverviewStats();

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('newsletter:sync-retry-due')->everyFiveMinutes();
        });
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(NewsletterServiceProvider::$packageName);
    }

    private function registerOverviewStats(): self
    {
        CapellAdmin::registerOverviewStat(
            key: 'newsletter_overview',
            label: fn (): string => __('capell-newsletter::widgets.subscribed'),
            value: fn (): int => SiteScope::applyForCurrentActor(Subscriber::query())
                ->where('status', SubscriberStatus::Subscribed)
                ->count(),
            group: fn (): string => __('capell-newsletter::settings.fieldset'),
            sort: 140,
            settingsLabel: fn (): string => __('capell-newsletter::widgets.overview'),
        );

        CapellAdmin::registerOverviewStat(
            key: 'newsletter_overview.pending',
            label: fn (): string => __('capell-newsletter::widgets.pending'),
            value: fn (): int => SiteScope::applyForCurrentActor(Subscriber::query())
                ->where('status', SubscriberStatus::Pending)
                ->count(),
            group: fn (): string => __('capell-newsletter::settings.fieldset'),
            sort: 141,
            settingsKey: 'newsletter_overview',
            settingsLabel: fn (): string => __('capell-newsletter::widgets.overview'),
        );

        CapellAdmin::registerOverviewStat(
            key: 'newsletter_overview.sync_failures',
            label: fn (): string => __('capell-newsletter::widgets.sync_failures'),
            value: fn (): int => SyncAttempt::query()
                ->whereIn('sync_status', [
                    SyncStatus::Failed,
                    SyncStatus::RetryScheduled,
                ])
                ->whereHas('subscriber', function (Builder $query): void {
                    SiteScope::applyForCurrentActor($query);
                })
                ->count(),
            group: fn (): string => __('capell-newsletter::settings.fieldset'),
            color: 'danger',
            sort: 142,
            settingsKey: 'newsletter_overview',
            settingsLabel: fn (): string => __('capell-newsletter::widgets.overview'),
        );

        return $this;
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
}
