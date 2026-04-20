<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Articles\Tables;

use Capell\Admin\Enums\FilamentColorEnum;
use Capell\Admin\Filament\Actions\Table\ReplicatePageAction;
use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\LanguagesColumn;
use Capell\Admin\Filament\Components\Tables\Columns\MediaLibraryImageColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageCachedIconColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\SiteColumn;
use Capell\Admin\Filament\Components\Tables\Columns\TypeColumn;
use Capell\Admin\Filament\Components\Tables\Filters\DateFilter;
use Capell\Admin\Filament\Contracts\HasPageResource;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Admin\Filament\Contracts\ValidatesDelete;
use Capell\Admin\Support\Loader\SiteLoader;
use Capell\Blog\Models\Tag;
use Capell\Core\Actions\GetEditPageResourceUrlAction;
use Capell\Core\Actions\PageDeletedAction;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore; // adjust if different namespace
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\Page as ResourcePage;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\LazyCollection;

class ArticlePagesTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(self::getTableQuery(...))
            ->defaultSort('updated_at', 'desc')
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
            ->filtersFormWidth('4xl')
            ->filtersFormColumns([
                'sm' => 2,
                'lg' => 3,
            ])
            ->columnManagerColumns(3)
            ->recordClasses(fn (Pageable $record): ?string => $record->deleted_at !== null ? 'table-row-warning' : null)
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    ReplicatePageAction::make(),
                    DeleteAction::make()
                        ->after(function (Pageable $record): void {
                            PageDeletedAction::run($record);
                        }),
                ])
                    ->color('gray'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->before(function (HasTable&ValidatesDelete $livewire, DeleteBulkAction $action, EloquentCollection|Collection|LazyCollection $records): void {
                        $records->each(function (Pageable $record) use ($livewire, $action): void {
                            if (! $livewire->validateDelete($record)) {
                                $action->cancel();
                            }
                        });
                    })
                    ->after(function (DeleteBulkAction $action, Collection $records): void {
                        $records->each(function (Pageable $record): void {
                            PageDeletedAction::run($record);
                        });
                    }),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make()
                    ->after(function (Pageable $record): void {
                        PageDeletedAction::run($record);
                    }),
            ])
            ->recordUrl(fn (Pageable $record): ?string => GetEditPageResourceUrlAction::run($record));
    }

    protected static function getTableQuery(Builder $query, HasTable $livewire): Builder
    {
        return $query
            ->whereHas('site', fn (BuilderContract $query): BuilderContract => $query->withTrashed())
            ->whereHas('type')
            ->with([
                'canonicalPage',
                'creator',
                'editor',
                'image',
                'site' => fn (BuilderContract $query): BuilderContract => $query->withTrashed(),
                'site.siteDomains',
                'translation' => fn (BuilderContract $query): BuilderContract => $query->with('language')
                    ->select(['translatable_id', 'translatable_type', 'language_id', 'title'])
                    ->when(
                        $livewire->getTableFilterState('filter')['language_id'],
                        fn (BuilderContract $query, int $id) => $query->where('language_id', $id),
                    ),
                'translations.language',
                'type',
                'pageUrls' => fn (BuilderContract $query): BuilderContract => $query->with('siteDomain')->ordered(),
                'pageUrl.siteDomain',
            ]);
    }

    protected static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            PageNameColumn::make('name')
                ->wrap()
                ->sortable()
                ->children(false)
                ->ancestors(false)
                ->searchable(
                    query: fn (Builder $query, string $search): Builder => $query->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas(
                            'translations',
                            fn (BuilderContract $query): BuilderContract => $query->where('title', 'like', sprintf('%%%s%%', $search)),
                        )
                        ->orderByRaw("CAST(IFNULL(NULLIF(POSITION(? IN pages.name), 0), 'void') AS UNSIGNED)", [$search]),
                )
                ->toggleable(),
            TextColumn::make('translation.title')
                ->label(__('capell-admin::table.title'))
                ->html()
                ->toggleable(isToggledHiddenByDefault: true),
            SiteColumn::make('site.name')
                ->color(FilamentColorEnum::LightGray->value)
                ->hidden(
                    fn (HasTable $livewire): bool => (($livewire instanceof ListRecords && $livewire->activeTab !== null)
                            && ! in_array($livewire->getTableFilterState('site_id'), [null, []], true))
                        || SiteLoader::getTotalSites() <= 1,
                ),
            TextColumn::make('url')
                ->label(__('capell-admin::table.url'))
                ->color('primary')
                ->disabledClick()
                ->html()
                ->searchable(
                    query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'pageUrl',
                        fn (BuilderContract $query): BuilderContract => $query->where('url', 'like', sprintf('%%%s%%', $search))
                            ->orWhereHas(
                                'site',
                                fn (BuilderContract $query): BuilderContract => $query->whereHas(
                                    'siteDomain',
                                    fn (BuilderContract $query): BuilderContract => $query->whereColumn(
                                        'site_domains.language_id',
                                        'page_urls.language_id',
                                    )
                                        ->when(
                                            DB::getDriverName() === 'sqlite',
                                            fn (Builder $query) => $query->whereRaw(
                                                "site_domains.scheme || '://' || site_domains.domain || site_domains.path || page_urls.url like ?",
                                                [sprintf('%%%s%%', $search)],
                                            ),
                                            fn (Builder $query) => $query->whereRaw(
                                                "CONCAT(site_domains.scheme, '://', site_domains.domain, COALESCE(site_domains.path, ''), page_urls.url) like ?",
                                                [sprintf('%%%s%%', $search)],
                                            ),
                                        ),
                                ),
                            )
                            ->orWhereHas(
                                'pageable',
                                fn (BuilderContract $query): BuilderContract => $query->where('name', 'like', sprintf('%%%s%%', $search)),
                            ),
                    ),
                )
                ->getStateUsing(function (Pageable $record, HasTable $livewire): ?HtmlString {
                    $pageUrl = null;
                    $languageId = $livewire->getTableFilterState('filter')['language_id'] ?? null;
                    if ($languageId !== null && $languageId !== '') {
                        $pageUrl = $record->pageUrls->firstWhere('language_id', $languageId);
                    }

                    if ($pageUrl === null) {
                        $pageUrl = $record->pageUrls->first();
                    }

                    if ($pageUrl === null) {
                        return null;
                    }

                    $short_url = str($pageUrl->url)->limit(40);

                    return new HtmlString("<a href='" . $pageUrl->full_url . "' target='_blank'>" . $short_url . '</a>');
                })
                ->toggleable(isToggledHiddenByDefault: true),
            MediaLibraryImageColumn::make('image')
                ->collection('image')
                ->toggleable()
                ->alignCenter()
                ->width(0),
            LanguagesColumn::make('translations.language'),
            TextColumn::make('layout.name')
                ->label(__('capell-admin::table.layout'))
                ->sortable()
                ->limit(30)
                ->size('sm')
                ->color(FilamentColorEnum::LightGray->value)
                ->toggleable()
                ->width(0),
            TypeColumn::make('type.name')
                ->toggleable(isToggledHiddenByDefault: true),
            PageCachedIconColumn::make('cached')
                ->toggleable(isToggledHiddenByDefault: true),
            DateColumn::make('created_at'),
            DateColumn::make('updated_at'),
            DateColumn::make('deleted_at'),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('site_id')
                ->label(__('capell-admin::form.site'))
                ->searchable()
                ->preload()
                ->relationship(
                    name: 'site',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query) => $query->ordered(),
                ),

            SelectFilter::make('layout_id')
                ->label(__('capell-admin::form.layout'))
                ->searchable()
                ->preload()
                ->relationship(
                    name: 'layout',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query) => $query->enabled()->ordered(),
                ),

            SelectFilter::make('type_id')
                ->label(__('capell-admin::form.page_type'))
                ->searchable()
                ->preload()
                ->relationship(
                    name: 'type',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query, ResourcePage|HasPageResource $livewire) => $query->enabled()
                        ->pageType()
                        ->adminResource($livewire::getResource()::getResourceName()),
                ),

            self::getTagsFilter(),

            Filter::make('filter')
                ->columnSpan(['default' => 1, 'md' => 3])
                ->columns(['default' => 1, 'md' => 3])
                ->schema([
                    Select::make('language_id')
                        ->label(__('capell-admin::table.language'))
                        ->searchable()
                        ->preload()
                        ->options(function (HasTable $livewire): array {
                            if (! $livewire->isTableLoaded()) {
                                return [];
                            }

                            return self::getLanguageSearchResults($livewire);
                        })
                        ->getSearchResultsUsing(
                            fn (HasTable $livewire, ?string $search): array => self::getLanguageSearchResults($livewire, $search),
                        ),
                ])
                ->query(function (Builder $query, array $data): void {
                    $query
                        ->when(
                            $data['language_id'] ?? null,
                            fn (Builder $query) => $query->whereHas(
                                'translations',
                                fn (BuilderContract $query): BuilderContract => $query->where(
                                    'language_id',
                                    (int) $data['language_id'],
                                ),
                            ),
                        )
                        ->when(
                            $data['canonical_page_id'] ?? null,
                            fn (Builder $query) => $query->where(
                                'meta->canonical_page_id',
                                $data['canonical_page_id'],
                            ),
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if (isset($data['language_id']) && $data['language_id'] !== null && $data['language_id'] !== '') {
                        /** @var class-string<Language> $model */
                        $model = CapellCore::getModel(ModelEnum::Language);

                        $indicators['language_id'] = __(
                            'capell-admin::filter.language',
                            ['search' => $model::query()->find($data['language_id'], 'name')?->name],
                        );
                    }

                    if (isset($data['canonical_page_id']) && $data['canonical_page_id'] !== null && $data['canonical_page_id'] !== '') {
                        /** @var class-string<Page> $model */
                        $model = CapellCore::getModel(\Capell\Blog\Enums\ModelEnum::Article);

                        $indicators['canonical_page_id'] = __(
                            'capell-admin::filter.canonical_page',
                            ['search' => $model::query()->where('id', $data['canonical_page_id'])->value('name')],
                        );
                    }

                    return $indicators;
                }),

            DateFilter::make('visible_from')
                ->label(__('capell-admin::form.publish_date')),

            TrashedFilter::make()
                ->native(false),
        ];
    }

    protected static function getLanguageSearchResults(HasTable $livewire, ?string $search = null): array
    {
        /* @var class-string<Language> $model */
        $model = CapellCore::getModel(ModelEnum::Language);

        $activeTabSiteId = $livewire instanceof ListRecords ? $livewire->activeTab : null;

        return $model::query()->when($activeTabSiteId, fn (Builder $query, int $siteId) => $query->whereHas(
            'sites',
            fn (BuilderContract $query): BuilderContract => $query->where('sites.id', $siteId),
        ))
            ->when(
                $search,
                fn (Builder $query, string $search) => $query
                    ->where('name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('code', 'like', sprintf('%%%s%%', $search)),
            )
            ->ordered()
            ->get()
            ->pluck('name', 'id')
            ->all();
    }

    protected static function getTagsFilter(): SelectFilter
    {
        return SelectFilter::make('tags')
            ->label(__('capell-blog::form.tags'))
            ->searchable()
            ->preload()
            ->relationship(
                name: 'tags',
                titleAttribute: 'name',
                modifyQueryUsing: function (Builder $query, HasTable $livewire): void {
                    $siteId = $livewire->activeTab;

                    if (in_array($siteId, [null, '', '0'], true)) {
                        $query->with('site')->orderBy('site_id');
                    } else {
                        $query->where(fn (Builder $builder): Builder => $builder->where('site_id', $siteId)->orWhereNull('site_id'));
                        $query->whereHas('pages', fn (BuilderContract $builder): BuilderContract => $builder->where('site_id', $siteId));
                    }

                    $languageId = $livewire->getTableFilterState('filter')['language_id'] ?? null;
                    if ($languageId !== null) {
                        /** @var class-string<Language> $model */
                        $model = CapellCore::getModel(ModelEnum::Language);

                        $code = $model::query()->find($languageId, 'code')?->code;
                        if ($code) {
                            $query->whereRaw('JSON_EXTRACT(`tags`.`name`, ' . DB::getPdo()->quote('$.' . $code) . ') IS NOT NULL');
                        }
                    }
                },
            )
            ->query(function (Builder $query, array $data): Builder {
                $value = $data['value'] ?? null;

                return $query->when(
                    $value,
                    fn (Builder $query): Builder => $query->whereHas('tags', fn (BuilderContract $builder): BuilderContract => $builder->where('tags.id', (int) $value)),
                );
            })
            ->indicateUsing(function (array $state): array {
                $indicators = [];
                $value = $state['value'] ?? null;
                if ($value) {
                    $indicators['tags'] = __(
                        'capell-mosaic::filter.tag',
                        ['search' => Tag::query()->find($value)?->name],
                    );
                }

                return $indicators;
            });
    }
}
