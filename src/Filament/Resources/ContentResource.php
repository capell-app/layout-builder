<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources;

use Capell\Admin\Filament\Components\Forms\TypeSchema;
use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Admin\Filament\Components\Tables\Columns\BadgeableColumn;
use Capell\Admin\Filament\Components\Tables\Columns\CuratorColumn;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\LanguagesColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\SiteColumn;
use Capell\Admin\Filament\Components\Tables\Columns\StatusColumn;
use Capell\Admin\Filament\Components\Tables\Columns\TypeNameColumn;
use Capell\Admin\Filament\Components\Tables\Filters\StatusFilter;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Enums\TagTypeEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models;
use Capell\Layout\Actions\ReplicateContentAction;
use Capell\Layout\Enums\LayoutModelEnum;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\SchemaEnum;
use Capell\Layout\Filament\Components\Forms\Content\ContentDetailsSchema;
use Capell\Layout\Filament\Components\Tables\Columns\Content\ContentNameColumn;
use Capell\Layout\Filament\Resources\ContentResource\Pages;
use Capell\Layout\Filament\Resources\ContentResource\RelationManagers\ContentAssetsRelationManager;
use Capell\Layout\Filament\Resources\ContentResource\RelationManagers\PagesRelationManager;
use Capell\Layout\Filament\Resources\ContentResource\RelationManagers\WidgetsRelationManager;
use Capell\Layout\Filament\Schemas\Content\DefaultContentSchema;
use Capell\Layout\Models\Content;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContentResource extends Resource
{
    protected static ?string $recordTitleAttribute = 'name';

    public static function getResourceType(): string
    {
        return 'Content';
    }

    public static function form(Form $form): Form
    {
        return $form->schema(self::getFormSchema($form));
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withDrafts()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getFormSchema(Form $form): array
    {
        return [
            Forms\Components\Grid::make()
                ->hiddenOn(['edit', 'editOption'])
                ->schema(ContentDetailsSchema::make()),
            TypeSchema::make()
                ->schema(
                    function (Get $get, TypeSchema $component) use ($form): array {
                        $typeId = $get('type_id');

                        $type = $typeId ? CapellCore::getModel(ModelEnum::Type)::find($typeId, ['admin']) : null;

                        $adminSchema = $type->admin['schema'] ?? DefaultContentSchema::getKey();

                        return $component->getSchema($form, SchemaEnum::Content->value, $adminSchema);
                    }
                ),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'translations.title'];
    }

    public static function getModel(): string
    {
        return CapellCore::getModel(LayoutModelEnum::Content->name);
    }

    public static function getNavigationBadge(): ?string
    {
        if (! config('capell-admin.resources.content.navigation_badge')) {
            return null;
        }

        return number_format(static::getModel()::count());
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_library'));
    }

    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-admin::navigation.contents'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContents::route('/'),
            'create' => Pages\CreateContent::route('/create'),
            'edit' => Pages\EditContent::route('/{record}/edit'),
        ];
    }

    public static function getNavigationIcon(): ?string
    {
        return CapellCore::getAsset(LayoutTypeEnum::Content->name)->getIcon();
    }

    public static function getPluralModelLabel(): string
    {
        return __('capell-admin::generic.contents');
    }

    public static function getRelations(): array
    {
        return [
            ContentAssetsRelationManager::class,
            WidgetsRelationManager::class,
            PagesRelationManager::class,
        ];
    }

    public static function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('site_id')
                ->label(__('capell-admin::form.site'))
                ->options(function (): array {
                    /** @var class-string<Models\Site> $model */
                    $model = CapellCore::getModel(ModelEnum::Site);

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
                        )
                ),

            Tables\Filters\SelectFilter::make('type_id')
                ->label(__('capell-admin::form.type'))
                ->relationship(
                    name: 'type',
                    titleAttribute: 'name',
                    /** @param Builder<Models\Type> $query */
                    modifyQueryUsing: fn (Builder $query): Builder => $query->where(
                        'type',
                        LayoutTypeEnum::Content->value
                    )
                        ->enabled()
                ),

            Tables\Filters\Filter::make('filter')
                ->columnSpan(['default' => 1, 'md' => 3])
                ->columns(['default' => 1, 'md' => 3])
                ->form([
                    Forms\Components\Select::make('language_id')
                        ->label(__('capell-admin::table.language'))
                        ->options(function (Tables\Contracts\HasTable $livewire): array {
                            $siteId = self::getSiteId($livewire);

                            /* @var class-string<\Capell\Core\Models\Language> $model */
                            $model = CapellCore::getModel(ModelEnum::Language);

                            return $model::when(
                                $siteId,
                                fn (Builder $query, int $siteId): Builder => $query->whereHas(
                                    'sites',
                                    fn (BuilderContract $query) => $query->where('sites.id', $siteId)
                                )
                            )
                                ->ordered()
                                ->pluck('name', 'id')
                                ->toArray();
                        }),

                    Forms\Components\Select::make('parent_uuid')
                        ->label(__('capell-admin::form.parent'))
                        ->options(function (Tables\Contracts\HasTable $livewire, Get $get) {
                            $siteId = self::getSiteId($livewire);

                            /** @var class-string<Content> $model */
                            $model = CapellCore::getModel(LayoutModelEnum::Content->name);

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
                                        fn (BuilderContract $query) => $query->where('translations.language_id', $languageId)
                                    )
                                )
                                ->orderBy('site_id')
                                ->orderBy('_lft')
                                ->get();

                            return $contents->mapWithKeys(function (Content $content) use ($siteId) {
                                $label = '';

                                if (! $siteId && $content->site) {
                                    $label .= $content->site->name.' » ';
                                }

                                if ($content->ancestors->isNotEmpty()) {
                                    $label .= $content->ancestors->pluck('name')
                                        ->map(fn ($item) => Str::limit($item, 30))
                                        ->implode(' » ')
                                        .' » ';
                                }

                                $label .= Str::limit($content->name, 40);

                                return [$content->uuid => $label];
                            });
                        }),

                    Forms\Components\Select::make('tags')
                        ->label(__('capell-admin::form.tags'))
                        ->relationship(name: 'tags', titleAttribute: 'name', modifyQueryUsing: function (Builder $query, Tables\Contracts\HasTable $livewire, Get $get): void {
                            $siteId = self::getSiteId($livewire);

                            if (! $siteId) {
                                $query->with('site')
                                    ->orderBy('site_id');
                            } else {
                                $query->where(
                                    fn (Builder $query) => $query->where('site_id', $siteId)->orWhereNull('site_id')
                                );
                                $query->whereHas('contents', fn (BuilderContract $query) => $query->where('site_id', $siteId));
                            }

                            if ($language_id = $get('language_id')) {
                                $code = CapellCore::getModel(ModelEnum::Language)::find($language_id, 'code')->code;
                                $query->whereRaw('JSON_EXTRACT(`tags`.`name`, '.DB::getPdo()->quote('$.'.$code).') IS NOT NULL');
                            }
                        })
                        ->getOptionLabelFromRecordUsing(function (Models\Tag $record, Tables\Contracts\HasTable $livewire, Get $get): string {
                            $label = '';

                            $siteId = self::getSiteId($livewire);

                            if (! $siteId && $record->site) {
                                $label .= $record->site->name.' » ';
                            }

                            if ($language_id = $get('language_id')) {
                                $code = CapellCore::getModel(ModelEnum::Language)::find($language_id, 'code')->code;

                                $label .= $record->getTranslation('name', $code);
                            } else {
                                $label .= $record->getTranslation('name', 'en');
                            }

                            return $label;
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
                                    (int) $data['language_id']
                                )
                            )
                        )
                        ->when(
                            $data['tags'] ?? null,
                            fn (Builder $query) => $query->whereHas(
                                'tags',
                                fn (BuilderContract $query) => $query->where(
                                    'tags.id',
                                    (int) $data['tags']
                                )
                            )
                        )
                        ->when(
                            $data['parent_uuid'] ?? null,
                            fn (Builder $query) => $query->where('parent_uuid', $data['parent_uuid'])
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if (! empty($data['language_id'])) {
                        /** @var class-string<Models\Language> $model */
                        $model = CapellCore::getModel(ModelEnum::Language);

                        $indicators['language_id'] = __(
                            'capell-admin::filter.language',
                            ['search' => $model::find($data['language_id'], 'name')?->name]
                        );
                    }

                    if (! empty($data['parent_uuid'])) {
                        /** @var class-string<Content> $model */
                        $model = CapellCore::getModel(LayoutModelEnum::Content->name);

                        $indicators['parent_uuid'] = __(
                            'capell-admin::filter.parent',
                            [
                                'search' => $model::select('name')->firstWhere(
                                    'uuid',
                                    $data['parent_uuid']
                                )
                                    ?->name,
                            ]
                        );
                    }

                    if (! empty($data['tags'])) {
                        /** @var class-string<Models\Tag> $model */
                        $model = CapellCore::getModel(ModelEnum::Tag);

                        $indicators['tags'] = __(
                            'capell-admin::filter.tag',
                            ['search' => $model::find($data['tags'], 'name')?->name]
                        );
                    }

                    return $indicators;
                }),

            Tables\Filters\SelectFilter::make('publish_status')
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

            Tables\Filters\TrashedFilter::make(),
        ];
    }

    public static function table(Table $table): Table
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
                        'tags',
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
                    ])
            )
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
            ->filtersFormWidth('4xl')
            ->filtersFormColumns([
                'sm' => 2,
                'lg' => 3,
            ])
            ->columnToggleFormColumns(3)
            ->actions([
                EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    ReplicateAction::make()
                        ->replicaModelAction(ReplicateContentAction::class),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
            ])
            ->recordClasses(fn (Content $record): ?string => match (true) {
                (bool) $record->deleted_at => 'table-row-warning',
                default => null,
            });
    }

    public static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            ContentNameColumn::make('name'),
            Tables\Columns\TextColumn::make('translation.title')
                ->label(__('capell-admin::table.title'))
                ->searchable()
                ->html()
                ->toggleable(isToggledHiddenByDefault: true),
            LanguagesColumn::make('translations.language'),
            Tables\Columns\TextColumn::make('parent.name')
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
            TypeNameColumn::make('type.name'),
            Tables\Columns\SpatieTagsColumn::make('tags')
                ->label(__('capell-admin::table.tags'))
                ->type(TagTypeEnum::CONTENT->value)
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('children_count')
                ->label(__('capell-admin::table.children'))
                ->alignCenter()
                ->numeric()
                ->sortable()
                ->toggleable()
                ->color('primary')
                ->url(
                    fn (Content $record, int $state): ?string => $state !== 0
                        ? self::getUrl('index', ['tableFilters' => ['filter' => ['parent_uuid' => $record->uuid]]])
                        : null
                ),
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
                    fn (Tables\Contracts\HasTable $livewire): bool => $livewire->activeTab
                        || ! empty($livewire->getTableFilterState('filter')['site_id'])
                ),
            CuratorColumn::make('asset.image_id')
                ->label(__('capell-admin::table.image'))
                ->relationship('image')
                ->toggleable(),
            StatusColumn::make('status'),
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

    private static function getSiteId(Tables\Contracts\HasTable $livewire)
    {
        return match (true) {
            $livewire instanceof Pages\ListContents => $livewire->activeTab,
            default => $livewire->getTableFilterState('filter')['site_id'] ?? null,
        };
    }
}
