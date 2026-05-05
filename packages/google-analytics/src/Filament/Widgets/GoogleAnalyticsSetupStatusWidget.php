<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\GoogleAnalytics\Actions\ResolveGoogleAnalyticsConfigAction;
use Capell\GoogleAnalytics\Models\GoogleAnalyticsSyncRun;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

final class GoogleAnalyticsSetupStatusWidget extends BaseWidget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'google_analytics_sync_status';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    protected static ?int $sort = 24;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRecords())
            ->queryStringIdentifier('google-analytics-sync-status')
            ->paginated(false)
            ->searchable(false)
            ->heading(__('capell-google-analytics::widgets.sync_status'))
            ->columns([
                TextColumn::make('label')
                    ->label(__('capell-google-analytics::widgets.metric')),
                TextColumn::make('value')
                    ->label(__('capell-google-analytics::widgets.value')),
            ]);
    }

    /**
     * @return Collection<int, array{id: string, label: string, value: string}>
     */
    private function getRecords(): Collection
    {
        $config = ResolveGoogleAnalyticsConfigAction::run();
        $latestRun = GoogleAnalyticsSyncRun::query()->latest('started_at')->first();

        return collect([
            [
                'id' => 'configured',
                'label' => __('capell-google-analytics::widgets.configured'),
                'value' => $config->enabled && $config->propertyId !== '' && $config->credentialsPath !== ''
                    ? __('capell-google-analytics::widgets.yes')
                    : __('capell-google-analytics::widgets.no'),
            ],
            [
                'id' => 'property-id',
                'label' => __('capell-google-analytics::settings.property_id'),
                'value' => $config->propertyId !== '' ? $config->propertyId : __('capell-google-analytics::widgets.not_set'),
            ],
            [
                'id' => 'last-sync',
                'label' => __('capell-google-analytics::widgets.last_sync'),
                'value' => $latestRun instanceof GoogleAnalyticsSyncRun && $latestRun->finished_at !== null
                    ? $latestRun->finished_at->toDateTimeString()
                    : __('capell-google-analytics::widgets.never'),
            ],
            [
                'id' => 'last-status',
                'label' => __('capell-google-analytics::widgets.last_status'),
                'value' => $latestRun instanceof GoogleAnalyticsSyncRun ? (string) $latestRun->status : __('capell-google-analytics::widgets.not_available'),
            ],
        ]);
    }
}
