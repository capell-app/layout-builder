<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Capell\Admin\Filament\Components\Tables\Actions\VisitUrlAction;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Resources\Sites\SiteResource;
use Capell\Admin\Support\Loader\SiteLoader;
use Capell\Blog\Enums\ModelEnum;
use Capell\Blog\Filament\Resources\Articles\ArticleResource;
use Capell\Blog\Models\Article;
use Capell\Core\Actions\GetEditPageResourceUrlAction;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Filament\Actions\Action;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListArticlesWidget extends BaseWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = ['default' => 2, 'sm' => 2, 'md' => 1];

    protected static ?int $sort = 5;

    public function getFilteredTableQuery(): Builder
    {
        $query = parent::getFilteredTableQuery();

        $languageId = $this->getTableFilterState('filter')['language_id'] ?? null;
        if ($languageId === null || $languageId === '') {
            return $query;
        }

        return $query->with([
            'translations' => fn (BuilderContract $query): BuilderContract => $query->when(
                DB::getDriverName() === 'sqlite',
                fn (BuilderContract $query): BuilderContract => $query->orderByRaw('CASE WHEN language_id = ? THEN 0 ELSE 1 END', [$languageId]),
                fn (BuilderContract $query): BuilderContract => $query->orderByRaw('FIELD(language_id, ?) DESC', [$languageId]),
            ),
            'url' => fn (BuilderContract $query): BuilderContract => $query->when(
                DB::getDriverName() === 'sqlite',
                fn (BuilderContract $query): BuilderContract => $query->orderByRaw('CASE WHEN language_id = ? THEN 0 ELSE 1 END', [$languageId]),
                fn (BuilderContract $query): BuilderContract => $query->orderByRaw('FIELD(language_id, ?) DESC', [$languageId]),
            ),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                function (): Builder {
                    /** @var class-string<Article> $model */
                    $model = CapellCore::getModel(ModelEnum::Article);

                    return $model::query()
                        ->with([
                            'creator',
                            'editor',
                            'site.siteDomains',
                            'translations.language',
                            'type',
                            'pageUrl.siteDomain',
                        ])
                        ->current();
                },
            )
            ->searchable(false)
            ->heading($this->getTableHeading())
            ->columns($this->getTableColumns())
            ->queryStringIdentifier('articles')
            ->filtersFormColumns(2)
            ->paginationPageOptions([5])
            ->recordUrl(fn (Pageable $record): ?string => GetEditPageResourceUrlAction::run($record))
            ->headerActions([
                Action::make('view-all')
                    ->label(__('capell-admin::button.view_all'))
                    ->button()
                    ->color('gray')
                    ->url(ArticleResource::getUrl()),
            ])
            ->recordActions([
                VisitUrlAction::make()
                    ->iconButton(),
            ])
            ->tap(function (Table $table): Table {
                $sitesCount = Site::query()->count();

                if ($sitesCount === 0) {
                    return $table->emptyStateHeading(__('capell-admin::generic.no_sites'))
                        ->emptyStateDescription(__('capell-admin::generic.no_sites_description'))
                        ->emptyStateIcon('heroicon-o-globe-alt')
                        ->emptyStateActions([
                            Action::make('createSite')
                                ->label(__('capell-admin::button.create_site'))
                                ->link()
                                ->url(SiteResource::getUrl('create')),
                        ]);
                }

                return $table;
            });
    }

    protected function getTableColumns(): array
    {
        return [
            Split::make([
                PageNameColumn::make('name')
                    ->withTypeIcon()
                    ->size(null)
                    ->children(false)
                    ->toggleable(false)
                    ->ancestors(false),
                Stack::make([
                    TextColumn::make('site.name')
                        ->label(__('capell-admin::table.site'))
                        ->visible(fn (): bool => SiteLoader::getTotalSites() > 1)
                        ->toggleable(false),
                    DateColumn::make('updated_at')
                        ->sortable(false)
                        ->toggleable(false)
                        ->since()
                        ->dateTimeTooltip(),
                ])
                    ->alignment(Alignment::End)
                    ->space(1)
                    ->grow(false),
            ]),
        ];
    }

    protected function getTableHeading(): string
    {
        return __('capell-admin::heading.latest_pages');
    }

    protected function paginateTableQuery(Builder $query): CursorPaginator
    {
        return $query->cursorPaginate(
            perPage: ($this->getTableRecordsPerPage() === 'all') ? $query->count() : $this->getTableRecordsPerPage(),
            cursorName: (in_array($this->getTable()->getQueryStringIdentifier(), [null, '', '0'], true) ? 'authentication-logs' : $this->getTable()->getQueryStringIdentifier()) . '_cursor',
        );
    }
}
