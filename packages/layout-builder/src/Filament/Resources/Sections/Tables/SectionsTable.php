<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Sections\Tables;

use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Admin\Filament\Components\Tables\Columns\BadgeableColumn;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\LanguagesColumn;
use Capell\Admin\Filament\Components\Tables\Columns\MediaLibraryImageColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\SiteColumn;
use Capell\Admin\Filament\Components\Tables\Columns\TypeColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\LayoutBuilder\Actions\ReplicateContentAction;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Filament\Components\Tables\Columns\Content\ContentNameColumn;
use Capell\LayoutBuilder\Models\Section;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\FormBuilder\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class SectionsTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query
                    ->with([
                        'ancestors.type',
                        'creator',
                        'editor',
                        'image',
                        'linkedPage',
                        'parent.type',
                        'site',
                        'translation.language',
                        'translations.language',
                        'type',
                    ])
                    ->withCount([
                        'children',
                        'assets',
                    ])
                    ->withoutGlobalScopes([
                        SoftDeletingScope::class,
                    ]),
            )
            ->defaultSort('updated_at', 'desc')
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
            ->filtersFormWidth('4xl')
            ->filtersFormColumns([
                'sm' => 2,
                'lg' => 3,
            ])
            ->columnManagerColumns(3)
            ->recordActions([
                EditAction::make('edit'),
                ActionGroup::make([
                    ReplicateAction::make('replicate')
                        ->replicaModelAction(ReplicateContentAction::class),
                    DeleteAction::make('delete'),
                ])
                    ->color('gray'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make('delete'),
                RestoreBulkAction::make('restore'),
                ForceDeleteBulkAction::make('forceDelete'),
            ])
            ->recordClasses(fn (Section $record): ?string => match (true) {
                (bool) $record->deleted_at => 'table-row-warning',
                default => null,
            });
    }

    public static function getSiteId(HasTable $livewire)
    {
        return match (true) {
            $livewire instanceof ListRecords => $livewire->activeTab,
            default => $livewire->getTableFilterState('filter')['site_id'] ?? null,
        };
    }

    protected static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            ContentNameColumn::make('name'),
            TextColumn::make('translation.title')
                ->label(__('capell-admin::table.title'))
                ->searchable()
                ->html()
                ->toggleable(isToggledHiddenByDefault: true),
            LanguagesColumn::make('translations.language'),
            TextColumn::make('parent.name')
                ->label(__('capell-admin::table.parent'))
                ->searchable()
                ->sortable()
                ->limit(60)
                ->toggleable(isToggledHiddenByDefault: true)
                ->linkRecord(),
            PageNameColumn::make('linkedPage.name')
                ->label(__('capell-admin::table.page'))
                // TODO does not work with json morph column
                ->searchable(false)
                ->withParents()
                ->toggleable(isToggledHiddenByDefault: true),
            TypeColumn::make('type.name'),
            MediaLibraryImageColumn::make('image')
                ->label(__('capell-admin::table.image'))
                ->collection('image')
                ->toggleable(),
            TextColumn::make('children_count')
                ->label(__('capell-layout-builder::table.children'))
                ->alignCenter()
                ->numeric()
                ->sortable()
                ->toggleable()
                ->color('primary')
                ->url(function (Section $record, int $state): ?string {
                    if ($state === 0) {
                        return null;
                    }

                    /** @var resource $resource */
                    $resource = AdminSurfaceLookup::resource(ResourceEnum::Page);

                    return $resource::getUrl(
                        'index',
                        ['filters' => ['filter' => ['parent_id' => $record->id]]],
                    );
                }),
            BadgeableColumn::make('assets_count')
                ->label(__('capell-layout-builder::table.assets'))
                ->alignCenter()
                ->numeric()
                ->sortable()
                ->toggleable()
                ->separator('')
                ->formatStateUsing(fn (Section $record): int => $record->assets_count),
            SiteColumn::make('site.name')
                ->hidden(
                    fn (HasTable $livewire): bool => ($livewire instanceof ListRecords && $livewire->activeTab)
                        || ($livewire->getTableFilterState('filter')['site_id'] ?? null) !== null && $livewire->getTableFilterState('filter')['site_id'] !== '',
                ),
            DateColumn::make('visible_from')
                ->label(__('capell-layout-builder::table.visible_from'))
                ->toggleable(isToggledHiddenByDefault: true),
            DateColumn::make('visible_until')
                ->label(__('capell-layout-builder::table.visible_until'))
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
                ->options(function (): array {
                    /** @var class-string<Site> $model */
                    $model = Site::class;

                    return $model::query()
                        ->ordered()
                        ->pluck('name', 'id')
                        ->prepend(__('capell-admin::generic.none'), 0)
                        ->toArray();
                })
                ->modifyQueryUsing(
                    fn (Builder $query, array $state): Builder => $query->when(
                        $state['value'],
                        fn (Builder $query, int $siteId): Builder => $query->where('site_id', $siteId),
                    )
                        ->when(
                            $state['value'] === 0,
                            fn (Builder $query): Builder => $query->whereNull('site_id'),
                        ),
                ),

            SelectFilter::make('type_id')
                ->label(__('capell-admin::form.type'))
                ->relationship(
                    name: 'type',
                    titleAttribute: 'name',
                    /** @param Builder<Type> $query */
                    modifyQueryUsing: fn (Builder $query): Builder => $query->where(
                        'type',
                        LayoutTypeEnum::Section->value,
                    )
                        ->enabled(),
                ),

            Filter::make('filter')
                ->columnSpan(['default' => 1, 'md' => 3])
                ->columns(['default' => 1, 'md' => 3])
                ->schema([
                    Select::make('language_id')
                        ->label(__('capell-admin::table.language'))
                        ->options(function (HasTable $livewire): array {
                            $siteId = static::getSiteId($livewire);

                            /* @var class-string<\Capell\Core\Models\Language> $model */
                            $model = Language::class;

                            return $model::query()->when($siteId, fn (Builder $query, int $siteId): Builder => $query->whereHas(
                                'sites',
                                fn (BuilderContract $query): BuilderContract => $query->where('sites.id', $siteId),
                            ))
                                ->ordered()
                                ->pluck('name', 'id')
                                ->toArray();
                        }),

                    Select::make('parent_id')
                        ->label(__('capell-admin::form.parent'))
                        ->allowHtml()
                        ->options(function (HasTable $livewire, Get $get): array {
                            $siteId = static::getSiteId($livewire);

                            /** @var class-string<Section> $model */
                            $model = Section::class;

                            $sections = $model::with([
                                'site',
                                'ancestors',
                            ])
                                ->whereHas('children')
                                ->whereHas('type', fn (BuilderContract $query): BuilderContract => $query->enabled())
                                ->when($siteId, fn (Builder $query): Builder => $query->where('site_id', $siteId))
                                ->when(
                                    $get('language_id'),
                                    fn (Builder $query, int $languageId): Builder => $query->whereHas(
                                        'translations',
                                        fn (BuilderContract $query): BuilderContract => $query->where('translations.language_id', $languageId),
                                    ),
                                )
                                ->orderBy('site_id')
                                ->orderBy('_lft')
                                ->get();

                            return $sections->mapWithKeys(function (Section $section) use ($siteId): array {
                                $label = '';

                                if (! $siteId && $section->site) {
                                    $label .= $section->site->name . ' &raquo; ';
                                }

                                $ancestors = $section->ancestors()->get();

                                if ($ancestors->isNotEmpty()) {
                                    $label .= $ancestors->pluck('name')
                                        ->map(fn (string $item): string => Str::limit($item, 30))
                                        ->implode(' &raquo; ')
                                        . ' &raquo; ';
                                }

                                $label .= Str::limit($section->name, 40);

                                return [$section->id => $label];
                            })
                                ->all();
                        }),
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
                            $data['parent_id'] ?? null,
                            fn (Builder $query) => $query->where('parent_id', $data['parent_id']),
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if (isset($data['language_id']) && $data['language_id'] !== null && $data['language_id'] !== '') {
                        /** @var class-string<Language> $model */
                        $model = Language::class;

                        $indicators['language_id'] = __(
                            'capell-admin::filter.language',
                            ['search' => $model::query()->find($data['language_id'], 'name')?->name],
                        );
                    }

                    if (isset($data['parent_id']) && $data['parent_id'] !== null && $data['parent_id'] !== '') {
                        /** @var class-string<Section> $model */
                        $model = Section::class;

                        $indicators['parent_id'] = __(
                            'capell-admin::filter.parent',
                            [
                                'search' => $model::query()->select('name')->firstWhere(
                                    'id',
                                    $data['parent_id'],
                                )
                                    ?->name,
                            ],
                        );
                    }

                    return $indicators;
                }),

            SelectFilter::make('publish_status')
                ->label(__('capell-layout-builder::table.publish_status'))
                ->placeholder(__('capell-admin::generic.all'))
                ->options([
                    'published' => __('capell-admin::generic.published'),
                    'unpublished' => __('capell-admin::generic.unpublished'),
                    'expired' => __('capell-admin::generic.expired'),
                ])
                ->query(fn (Builder $query, array $state): Builder => match ($state['value'] ?? null) {
                    'published' => $query->publishedDate(),
                    'unpublished' => $query->pending(),
                    'expired' => $query->expired(),
                    default => $query,
                }),

            TrashedFilter::make(),
        ];
    }
}
