<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\GA4Reports\Actions\BuildTopGA4ReportsPagesAction;
use Capell\GA4Reports\Data\GA4ReportsTopPageData;
use Capell\GA4Reports\Filament\Widgets\Concerns\BuildsGA4ReportsDashboardWindow;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

final class GA4ReportsTopPagesWidget extends BaseWidget implements CapellWidgetContract
{
    use BuildsGA4ReportsDashboardWindow;
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'ga4_reports_top_pages';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 2];

    protected static ?int $sort = 23;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRecords())
            ->queryStringIdentifier('ga4-reports-top-pages')
            ->paginated(false)
            ->searchable(false)
            ->heading(__('capell-ga4-reports::widgets.top_pages'))
            ->columns([
                TextColumn::make('page_path')
                    ->label(__('capell-ga4-reports::widgets.page_path')),
                TextColumn::make('page_title')
                    ->label(__('capell-ga4-reports::widgets.page_title')),
                TextColumn::make('screen_page_views')
                    ->label(__('capell-ga4-reports::widgets.screen_page_views'))
                    ->numeric(),
                TextColumn::make('sessions')
                    ->label(__('capell-ga4-reports::widgets.sessions'))
                    ->numeric(),
                TextColumn::make('total_users')
                    ->label(__('capell-ga4-reports::widgets.total_users'))
                    ->numeric(),
                TextColumn::make('conversions')
                    ->label(__('capell-ga4-reports::widgets.conversions'))
                    ->numeric(),
            ]);
    }

    /**
     * @return Collection<int, array{id: string, page_path: string, page_title: ?string, screen_page_views: int, sessions: int, total_users: int, conversions: int}>
     */
    private function getRecords(): Collection
    {
        return collect(BuildTopGA4ReportsPagesAction::run($this->getGA4ReportsWindow(), 10))
            ->map(fn (GA4ReportsTopPageData $page, int $index): array => [
                'id' => 'ga4-reports-page-' . $index,
                'page_path' => $page->pagePath,
                'page_title' => $page->pageTitle,
                'screen_page_views' => $page->screenPageViews,
                'sessions' => $page->sessions,
                'total_users' => $page->totalUsers,
                'conversions' => $page->conversions,
            ]);
    }
}
