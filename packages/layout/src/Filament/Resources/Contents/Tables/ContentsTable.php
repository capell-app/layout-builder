<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Contents\Tables;

use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Admin\Filament\Components\Tables\Columns\BadgeableColumn;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\LanguagesColumn;
use Capell\Admin\Filament\Components\Tables\Columns\MediaLibraryImageColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\PublishIconColumn;
use Capell\Admin\Filament\Components\Tables\Columns\SiteColumn;
use Capell\Admin\Filament\Components\Tables\Columns\TypeColumn;
use Capell\Admin\Filament\Components\Tables\Filters\StatusFilter;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Layout\Actions\ReplicateContentAction;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Filament\Components\Tables\Columns\Content\ContentNameColumn;
use Capell\Layout\Models\Content;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
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

class ContentsTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query
                    ->with([
                        'ancestors',
                        'creator',
                        'editor',
                        'image',
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
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
            ->filtersFormWidth('4xl')
            ->filtersFormColumns([
                'sm' => 2,
                'lg' => 3,
            ])
            ->columnManagerColumns(3)
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    ReplicateAction::make()
                        ->replicaModelAction(ReplicateContentAction::class),
                    DeleteAction::make(),
                ])
                    ->color('gray'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ])
            ->recordClasses(fn (Content $record): ?string => match (true) {
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
                ->linkRecord()
                ->toggleable(isToggledHiddenByDefault: true),
            PageNameColumn::make('linkedPage.name')
                ->label(__('capell-admin::table.page'))
                ->withParents()
                ->toggleable(isToggledHiddenByDefault: true),
            TypeColumn::make('type.name'),
            TextColumn::make('children_count')
                ->label(__('capell-admin::table.children'))
                ->alignCenter()
                ->numeric()
                ->sortable()
                ->toggleable()
                ->color('primary')
                ->url(function (Content $record, int $state): ?string {
                    if ($state === 0) {
                        return null;
                    }

                    /** @var resource $resource */
                    $resource = CapellAdmin::getResource(ResourceEnum::Page);

                    return $resource::getUrl(
                        'index',
                        ['tableFilters' => ['filter' => ['parent_id' => $record->id]]],
                    );
                }),
            MediaLibraryImageColumn::make('image')
                ->label(__('capell-admin::table.image'))
                ->collection('image')
                ->toggleable(),
            BadgeableColumn::make('assets_count')
                ->label(__('capell-admin::table.assets'))
                ->alignCenter()
                ->numeric()
                ->sortable()
                ->toggleable()
                ->separator('')
                ->formatStateUsing(fn (Content $record): string => $record->assets_count ? '' : ' &mdash; '),
            SiteColumn::make('site.name')
                ->hidden(
                    fn (HasTable $livewire): bool => $livewire->activeTab
                        || ! empty($livewire->getTableFilterState('filter')['site_id']),
                ),
            PublishIconColumn::make('status'),
            DateColumn::make('publish_from')
                ->label(__('capell-admin::table.publish_from'))
                ->toggleable(isToggledHiddenByDefault: true),
            DateColumn::make('publish_to')
                ->label(__('capell-admin::table.publish_to'))
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
                    $model = CapellCore::getModel(CoreModelEnum::Site);

                    return $model::query()
                        ->ordered()
                        ->pluck('name', 'id')
                        ->prepend(__('capell-admin::form.none'), 0)
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
                        LayoutTypeEnum::Content->value,
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
                            $model = CapellCore::getModel(CoreModelEnum::Language);

                            return $model::when(
                                $siteId,
                                fn (Builder $query, int $siteId): Builder => $query->whereHas(
                                    'sites',
                                    fn (BuilderContract $query) => $query->where('sites.id', $siteId),
                                ),
                            )
                                ->ordered()
                                ->pluck('name', 'id')
                                ->toArray();
                        }),

                    Select::make('parent_id')
                        ->label(__('capell-admin::form.parent'))
                        ->allowHtml()
                        ->options(function (HasTable $livewire, Get $get) {
                            $siteId = static::getSiteId($livewire);

                            /** @var class-string<Content> $model */
                            $model = CapellCore::getModel(ModelEnum::Content->name);

                            $contents = $model::with([
                                'site',
                                'ancestors',
                            ])
                                ->whereHas('children')
                                ->whereHas('type', fn (BuilderContract $query) => $query->enabled())
                                ->when($siteId, fn (Builder $query) => $query->where('site_id', $siteId))
                                ->when(
                                    $get('language_id'),
                                    fn (Builder $query, $languageId) => $query->whereHas(
                                        'translations',
                                        fn (BuilderContract $query) => $query->where('translations.language_id', $languageId),
                                    ),
                                )
                                ->orderBy('site_id')
                                ->orderBy('_lft')
                                ->get();

                            return $contents->mapWithKeys(function (Content $content) use ($siteId): array {
                                $label = '';

                                if (! $siteId && $content->site) {
                                    $label .= $content->site->name . ' &raquo; ';
                                }

                                $ancestors = $content->ancestors()->get();

                                if ($ancestors->isNotEmpty()) {
                                    $label .= $ancestors->pluck('name')
                                        ->map(fn ($item) => Str::limit($item, 30))
                                        ->implode(' &raquo; ')
                                        . ' &raquo; ';
                                }

                                $label .= Str::limit($content->name, 40);

                                return [$content->id => $label];
                            });
                        }),
                ])
                ->query(function (Builder $query, array $data): void {
                    $query
                        ->when(
                            $data['language_id'] ?? null,
                            fn (Builder $query) => $query->whereHas(
                                'translations',
                                fn (BuilderContract $query) => $query->where(
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

                    if (! empty($data['language_id'])) {
                        /** @var class-string<Language> $model */
                        $model = CapellCore::getModel(CoreModelEnum::Language);

                        $indicators['language_id'] = __(
                            'capell-admin::filter.language',
                            ['search' => $model::find($data['language_id'], 'name')?->name],
                        );
                    }

                    if (! empty($data['parent_id'])) {
                        /** @var class-string<Content> $model */
                        $model = CapellCore::getModel(ModelEnum::Content->name);

                        $indicators['parent_id'] = __(
                            'capell-admin::filter.parent',
                            [
                                'search' => $model::select('name')->firstWhere(
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
                ->label(__('capell-admin::table.publish_status'))
                ->placeholder(__('capell-admin::generic.all'))
                ->options([
                    'published' => __('capell-admin::generic.published'),
                    'unpublished' => __('capell-admin::generic.unpublished'),
                    'expired' => __('capell-admin::generic.expired'),
                ])
                ->query(fn (Builder $query, array $state): Builder => match ($state['value'] ?? null) {
                    'published' => $query->published(),
                    'unpublished' => $query->pending(),
                    'expired' => $query->expired(),
                    default => $query,
                }),

            StatusFilter::make('status'),

            TrashedFilter::make(),
        ];
    }
}
