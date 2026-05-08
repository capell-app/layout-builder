<?php

declare(strict_types=1);

namespace Capell\Newsletter\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Admin\Support\SiteScope;
use Capell\Newsletter\Enums\SubscriberStatus;
use Capell\Newsletter\Enums\SyncStatus;
use Capell\Newsletter\Models\Subscriber;
use Capell\Newsletter\Models\SyncAttempt;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class NewsletterOverviewStatsWidget extends StatsOverviewWidget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'newsletter_overview';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full'];

    protected static ?int $sort = 25;

    protected function getStats(): array
    {
        return [
            Stat::make(
                __('capell-newsletter::widgets.subscribed'),
                (string) SiteScope::applyForCurrentActor(Subscriber::query())->where('status', SubscriberStatus::Subscribed)->count(),
            ),
            Stat::make(
                __('capell-newsletter::widgets.pending'),
                (string) SiteScope::applyForCurrentActor(Subscriber::query())->where('status', SubscriberStatus::Pending)->count(),
            ),
            Stat::make(
                __('capell-newsletter::widgets.sync_failures'),
                (string) SyncAttempt::query()
                    ->whereIn('sync_status', [
                        SyncStatus::Failed,
                        SyncStatus::RetryScheduled,
                    ])
                    ->whereHas('subscriber', function (Builder $query): void {
                        SiteScope::applyForCurrentActor($query);
                    })
                    ->count(),
            ),
        ];
    }
}
