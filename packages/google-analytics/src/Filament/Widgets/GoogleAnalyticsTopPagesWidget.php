<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\GoogleAnalytics\Actions\BuildTopGoogleAnalyticsPagesAction;
use Capell\GoogleAnalytics\Data\GoogleAnalyticsTopPageData;
use Capell\GoogleAnalytics\Filament\Widgets\Concerns\BuildsGoogleAnalyticsDashboardWindow;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

final class GoogleAnalyticsTopPagesWidget extends BaseWidget implements CapellWidgetContract
{
    use BuildsGoogleAnalyticsDashboardWindow;
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'google_analytics_top_pages';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 2];

    protected static ?int $sort = 23;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRecords())
            ->queryStringIdentifier('google-analytics-top-pages')
            ->paginated(false)
            ->searchable(false)
            ->heading(__('capell-google-analytics::widgets.top_pages'))
            ->columns([
                TextColumn::make('page_path')
                    ->label(__('capell-google-analytics::widgets.page_path')),
                TextColumn::make('page_title')
                    ->label(__('capell-google-analytics::widgets.page_title')),
                TextColumn::make('screen_page_views')
                    ->label(__('capell-google-analytics::widgets.screen_page_views'))
                    ->numeric(),
                TextColumn::make('sessions')
                    ->label(__('capell-google-analytics::widgets.sessions'))
                    ->numeric(),
                TextColumn::make('total_users')
                    ->label(__('capell-google-analytics::widgets.total_users'))
                    ->numeric(),
                TextColumn::make('conversions')
                    ->label(__('capell-google-analytics::widgets.conversions'))
                    ->numeric(),
            ]);
    }

    /**
     * @return Collection<int, array{id: string, page_path: string, page_title: ?string, screen_page_views: int, sessions: int, total_users: int, conversions: int}>
     */
    private function getRecords(): Collection
    {
        return collect(BuildTopGoogleAnalyticsPagesAction::run($this->getGoogleAnalyticsWindow(), 10))
            ->map(fn (GoogleAnalyticsTopPageData $page, int $index): array => [
                'id' => 'google-analytics-page-' . $index,
                'page_path' => $page->pagePath,
                'page_title' => $page->pageTitle,
                'screen_page_views' => $page->screenPageViews,
                'sessions' => $page->sessions,
                'total_users' => $page->totalUsers,
                'conversions' => $page->conversions,
            ]);
    }
}
