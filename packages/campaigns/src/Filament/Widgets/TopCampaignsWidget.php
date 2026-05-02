<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Campaigns\Actions\BuildTopCampaignsQueryAction;
use Capell\Campaigns\Data\Dashboard\CampaignConversionSummaryData;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

final class TopCampaignsWidget extends TableWidget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'top_campaigns';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 1];

    protected static ?int $sort = 21;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->records())
            ->heading(__('capell-campaigns::widgets.top_campaigns'))
            ->paginated(false)
            ->columns([
                TextColumn::make('campaign')
                    ->label(__('capell-campaigns::widgets.campaign')),
                TextColumn::make('visits')
                    ->label(__('capell-campaigns::widgets.visits'))
                    ->numeric(),
                TextColumn::make('conversions')
                    ->label(__('capell-campaigns::widgets.conversions'))
                    ->numeric(),
                TextColumn::make('conversion_rate')
                    ->label(__('capell-campaigns::widgets.conversion_rate')),
            ]);
    }

    /**
     * @return Collection<int, array{id: int, campaign: string, visits: int, conversions: int, conversion_rate: string}>
     */
    private function records(): Collection
    {
        return BuildTopCampaignsQueryAction::run()
            ->map(fn (CampaignConversionSummaryData $summary): array => [
                'id' => $summary->campaignGroupId,
                'campaign' => $summary->campaignName,
                'visits' => $summary->visits,
                'conversions' => $summary->conversions,
                'conversion_rate' => $summary->conversionRate . '%',
            ]);
    }
}
