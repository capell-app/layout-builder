<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\GA4Reports\Actions\ResolveGA4ReportsConfigAction;
use Capell\GA4Reports\Models\GA4ReportsSyncRun;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

final class GA4ReportsSetupStatusWidget extends BaseWidget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'ga4_reports_sync_status';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    protected static ?int $sort = 24;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRecords())
            ->queryStringIdentifier('ga4-reports-sync-status')
            ->paginated(false)
            ->searchable(false)
            ->heading(__('capell-ga4-reports::widgets.sync_status'))
            ->columns([
                TextColumn::make('label')
                    ->label(__('capell-ga4-reports::widgets.metric')),
                TextColumn::make('value')
                    ->label(__('capell-ga4-reports::widgets.value')),
            ]);
    }

    /**
     * @return Collection<int, array{id: string, label: string, value: string}>
     */
    private function getRecords(): Collection
    {
        $config = ResolveGA4ReportsConfigAction::run();
        $latestRun = GA4ReportsSyncRun::query()->latest('started_at')->first();

        return collect([
            [
                'id' => 'configured',
                'label' => __('capell-ga4-reports::widgets.configured'),
                'value' => $config->enabled && $config->propertyId !== '' && $config->credentialsPath !== ''
                    ? __('capell-ga4-reports::widgets.yes')
                    : __('capell-ga4-reports::widgets.no'),
            ],
            [
                'id' => 'property-id',
                'label' => __('capell-ga4-reports::settings.property_id'),
                'value' => $config->propertyId !== '' ? $config->propertyId : __('capell-ga4-reports::widgets.not_set'),
            ],
            [
                'id' => 'last-sync',
                'label' => __('capell-ga4-reports::widgets.last_sync'),
                'value' => $latestRun instanceof GA4ReportsSyncRun && $latestRun->finished_at !== null
                    ? $latestRun->finished_at->toDateTimeString()
                    : __('capell-ga4-reports::widgets.never'),
            ],
            [
                'id' => 'last-status',
                'label' => __('capell-ga4-reports::widgets.last_status'),
                'value' => $latestRun instanceof GA4ReportsSyncRun ? (string) $latestRun->status : __('capell-ga4-reports::widgets.not_available'),
            ],
        ]);
    }
}
