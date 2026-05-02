<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Campaigns\Actions\BuildTopLandingPagesQueryAction;
use Capell\Campaigns\Data\Dashboard\CampaignLandingPageSummaryData;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

final class TopLandingPagesWidget extends TableWidget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'top_landing_pages';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 1];

    protected static ?int $sort = 22;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->records())
            ->heading(__('capell-campaigns::widgets.top_landing_pages'))
            ->paginated(false)
            ->columns([
                TextColumn::make('landing_page')
                    ->label(__('capell-campaigns::widgets.landing_page')),
                TextColumn::make('campaign')
                    ->label(__('capell-campaigns::widgets.campaign')),
                TextColumn::make('conversions')
                    ->label(__('capell-campaigns::widgets.conversions'))
                    ->numeric(),
            ]);
    }

    /**
     * @return Collection<int, array{id: int, landing_page: string, campaign: string, conversions: int}>
     */
    private function records(): Collection
    {
        return BuildTopLandingPagesQueryAction::run()
            ->map(fn (CampaignLandingPageSummaryData $summary): array => [
                'id' => $summary->landingPageId,
                'landing_page' => $summary->landingPageName,
                'campaign' => $summary->campaignName,
                'conversions' => $summary->conversions,
            ]);
    }
}
