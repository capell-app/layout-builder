<?php

declare(strict_types=1);

namespace Capell\SeoTools\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\Admin\Support\SafeAdminUrl;
use Capell\Admin\Support\SiteScope;
use Capell\Core\Models\AccessLog;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\DeleteBulkAction;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Override;

class NotFoundUrlsPage extends Page implements HasActions, HasTable
{
    use HasNavigationBadge;
    use HasPageShield;
    use InteractsWithActions;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::ExclamationTriangle;

    protected string $view = 'capell-admin::components.pages.table';

    protected static ?string $slug = 'missing-pages';

    /**
     * @return class-string<AccessLog>
     */
    public static function getModel(): string
    {
        return AccessLog::class;
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<AccessLog> $query */
        $query = SiteScope::applyForCurrentActor(AccessLog::query());

        return $query->notFound();
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-admin::navigation.not_found'));
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_monitoring'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                static::getEloquentQuery()
                    ->select([
                        'url',
                        DB::raw('MAX(viewed_at) as last_viewed_at'),
                        DB::raw('COUNT(DISTINCT session_id) as total_visitors'),
                    ])
                    ->groupBy('url'),
            )
            ->columns([
                TextColumn::make('url')
                    ->label(__('capell-admin::table.url'))
                    ->size('sm')
                    ->sortable()
                    ->searchable()
                    ->disabledClick()
                    ->html()
                    ->formatStateUsing(
                        fn (AccessLog $record): HtmlString => self::formatUrlLink($record),
                    ),
                DateColumn::make('last_viewed_at')
                    ->label(__('capell-admin::table.last_viewed_at'))
                    ->size('sm')
                    ->sortable(),
                TextColumn::make('total_visitors')
                    ->label(__('capell-admin::table.total_visitors'))
                    ->alignCenter()
                    ->size('sm')
                    ->sortable()
                    ->numeric(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->using(function (self $livewire): void {
                        static::getEloquentQuery()
                            ->whereIn('url', $livewire->selectedTableRecords)
                            ->get()
                            ->each
                            ->delete();
                    }),
            ])
            ->defaultSort('total_visitors', 'desc');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-admin::generic.page_not_found_info');
    }

    /**
     * @param  AccessLog  $record
     */
    public function getTableRecordKey(Model|array $record): string
    {
        return $record->url;
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return __('capell-admin::heading.page_not_found');
    }

    private static function formatUrlLink(AccessLog $record): HtmlString
    {
        $url = e($record->url);
        $href = SafeAdminUrl::href($record->url);

        if ($href === null) {
            return new HtmlString($url);
        }

        return new HtmlString(sprintf('<a href="%s" target="_blank">%s</a>', e($href), $url));
    }
}
