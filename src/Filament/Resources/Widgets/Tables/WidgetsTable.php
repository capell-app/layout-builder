<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Widgets\Tables;

use Capell\Admin\Enums\FilamentColorEnum;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\LanguagesColumn;
use Capell\Admin\Filament\Components\Tables\Columns\MediaLibraryImageColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\StatusIconColumn;
use Capell\Admin\Filament\Components\Tables\Filters\StatusFilter;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Admin\Support\SiteScope;
use Capell\Core\Data\Database\SqlFragment;
use Capell\Core\Facades\CapellDatabase;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Actions\BuildWidgetDeletionImpactAction;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Filament\Resources\Layouts\Tables\LayoutsTable;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\ListWidgets;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as BaseQueryBuilder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class WidgetsTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query->with([
                    'creator',
                    'editor',
                    'type',
                    'translations.language',
                ])
                    ->select('widgets.*')
                    /** @phpstan-ignore-next-line Widget exposes this local scope through Eloquent. */
                    ->withLayoutsCount(LayoutResource::getEloquentQuery()),
            )
            ->defaultSort('name')
            ->columns(self::getTableColumns())
            ->filters(self::getTableFilters())
            ->recordClasses(fn (Widget $record): ?string => match (true) {
                (bool) $record->deleted_at => 'table-row-warning',
                default => null,
            })
            ->recordActions([
                EditAction::make('edit'),
                LayoutsTable::getBulkChangeLayoutsAction('moveOrReplaceInLayouts'),
                ActionGroup::make([
                    ReplicateAction::make('replicate'),
                    DeleteAction::make('delete')
                        ->modalDescription(fn (Widget $record): string => self::deletionImpactDescription($record)),
                ])
                    ->color('gray'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make('delete'),
                ForceDeleteBulkAction::make('forceDelete'),
                RestoreBulkAction::make('restore'),
            ]);
    }

    /**
     * @return array<array-key, mixed>
     */
    protected static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            NameColumn::make('name')
                ->searchable([
                    'name',
                    'admin->notes',
                    'component',
                    'component_item',
                    'view_file',
                ]),
            TextColumn::make('type.name')
                ->label(__('capell-admin::table.type'))
                ->badge()
                ->searchable()
                ->sortable(),
            MediaLibraryImageColumn::make('meta.image')
                ->toggleable(isToggledHiddenByDefault: true),
            LanguagesColumn::make('translations.language'),
            TextColumn::make('translation.content')
                ->label(__('capell-admin::table.content'))
                ->sortable()
                ->searchable(query: self::applyContentSearch(...))
                ->limit(200)
                ->wrap()
                ->color(FilamentColorEnum::LightGray->value)
                ->html()
                ->listWithLineBreaks()
                ->toggleable(isToggledHiddenByDefault: true)
                ->formatStateUsing(
                    fn (ListWidgets $livewire, TextColumn $column, Widget $record): string => Str::limit(
                        $record->translation->title ?? '',
                        $column->getCharacterLimit() ?? 200,
                        $column->getCharacterLimitEnd() ?? '...',
                    ),
                )
                ->description(function (ListWidgets $livewire, TextColumn $column, Widget $record): ?HtmlString {
                    if ($record->translation?->content === null) {
                        return null;
                    }

                    return new HtmlString(
                        Str::limit(
                            $record->translation->content,
                            $column->getCharacterLimit() ?? 200,
                            $column->getCharacterLimitEnd() ?? '...',
                        ),
                    );
                }),
            TextColumn::make('key')
                ->label(__('capell-admin::table.key'))
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('component')
                ->label(__('capell-admin::table.component'))
                ->searchable(query: self::applyComponentSearch(...))
                ->size('xs')
                ->color(FilamentColorEnum::LightGray->value)
                ->formatStateUsing(function (Widget $record): ?HtmlString {
                    $components = [
                        __('capell-admin::form.component') => $record->component ?? '',
                        __('capell-admin::form.file') => $record->view_file ?? '',
                        __('capell-admin::form.component_item') => $record->component_item ?? '',
                    ];

                    $components = array_filter($components, fn (string $value): bool => $value !== '');

                    if ($components === []) {
                        return null;
                    }

                    array_walk(
                        $components,
                        fn (string $value, string $key): string => sprintf('%s: %s', $key, $value),
                    );

                    return new HtmlString(implode('<br />', $components));
                })
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('widget_assets_count')
                ->label(__('capell-admin::table.total_assets'))
                ->counts('widgetAssets')
                ->sortable()
                ->alignCenter()
                ->numeric()
                ->toggleable(),
            TextColumn::make('layouts_count')
                ->label(__('capell-admin::table.total_layouts'))
                ->sortable()
                ->alignCenter()
                ->numeric()
                ->toggleable()
                ->disabledClick()
                ->formatStateUsing(
                    function (Widget $record, int $state): ?HtmlString {
                        if ($state === 0 || ! self::hasAuthoritativeLayoutUsage()) {
                            return null;
                        }

                        return new HtmlString(
                            Blade::render(
                                'capell-admin::components.tables.url',
                                [
                                    'state' => $state,
                                    'url' => AdminSurfaceLookup::resource(
                                        ResourceEnum::Layout,
                                        LayoutBuilderAdminRegistrar::LAYOUT_RESOURCE_NAME,
                                    )::getUrl('index', ['filters[widget_key][value]' => $record->key]),
                                ],
                            ),
                        );
                    },
                ),
            TextColumn::make('usage_status')
                ->label(__('capell-layout-builder::table.usage'))
                ->badge()
                ->getStateUsing(fn (Widget $record): string => (int) $record->layouts_count === 0
                    ? (string) (self::hasAuthoritativeLayoutUsage()
                        ? __('capell-layout-builder::table.unused')
                        : __('capell-layout-builder::table.widget_usage_no_tracked_uses'))
                    : (string) trans_choice('capell-layout-builder::table.widget_usage_layouts', (int) $record->layouts_count, ['count' => (int) $record->layouts_count]))
                ->color(fn (Widget $record): string => (int) $record->layouts_count === 0 ? 'warning' : 'success')
                ->tooltip(fn (Widget $record): string => (int) $record->layouts_count === 0
                    ? (string) (self::hasAuthoritativeLayoutUsage()
                        ? __('capell-layout-builder::table.widget_usage_unused_tooltip')
                        : __('capell-layout-builder::table.widget_usage_no_tracked_uses_tooltip'))
                    : (string) trans_choice('capell-layout-builder::table.widget_usage_layouts_tooltip', (int) $record->layouts_count, ['count' => (int) $record->layouts_count]))
                ->toggleable(),
            StatusIconColumn::make('status'),
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

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected static function applyContentSearch(Builder $query, string $search): Builder
    {
        return $query->whereRelation(
            'translations',
            'content',
            'like',
            $search,
        );
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected static function applyComponentSearch(Builder $query, string $search): Builder
    {
        $grammar = $query->getQuery()->getGrammar();
        $dialect = CapellDatabase::for($query->getModel())->queryDialect();

        return $query->where(
            function (Builder $query) use ($dialect, $grammar, $search): void {
                foreach (['component', 'view_file', 'component_item'] as $index => $column) {
                    $position = $dialect->textPosition(
                        SqlFragment::raw($grammar->wrap($column)),
                        $search,
                        caseInsensitive: true,
                    );

                    (new SqlFragment(
                        $position->sql . ' > 0',
                        $position->bindings,
                    ))->applyWhere(
                        $query->getQuery(),
                        $index === 0 ? 'and' : 'or',
                    );
                }
            },
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('blueprint_id')
                ->label(__('capell-layout-builder::form.widget_type'))
                ->relationship(
                    name: 'type',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query): Builder => $query->where(
                        'type',
                        LayoutTypeEnum::Widget,
                    ),
                ),

            SelectFilter::make('layout_id')
                ->label(__('capell-admin::form.layout'))
                ->options(fn (): array => Layout::query()
                    ->ordered()
                    ->pluck('name', 'id')
                    ->all())
                ->modifyQueryUsing(
                    fn (Builder $query, array $state): Builder => $query->when(
                        isset($state['value']) && $state['value'] !== '',
                        fn (Builder $query): Builder => self::whereUsedByLayout($query, (int) $state['value']),
                    ),
                ),

            Filter::make('filter')
                ->schema([
                    Select::make('language_id')
                        ->label(__('capell-admin::table.language'))
                        ->options(function (): array {
                            /* @var class-string<\Capell\Core\Models\Language> $model */
                            $model = Language::class;

                            return $model::query()->ordered()
                                ->pluck('name', 'id')
                                ->toArray();
                        }),
                ])
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if (isset($data['language_id']) && $data['language_id'] !== '') {
                        /** @var class-string<Language> $model */
                        $model = Language::class;

                        $indicators['language_id'] = __(
                            'capell-admin::filter.language',
                            ['search' => $model::query()->find((int) $data['language_id'], ['name'])?->name],
                        );
                    }

                    return $indicators;
                })
                ->query(
                    fn (Builder $query, array $data): Builder => $query->when(
                        $data['language_id'],
                        fn (Builder $query): Builder => $query->whereHas(
                            'translations',
                            fn (BuilderContract $query): BuilderContract => $query->where(
                                'language_id',
                                (int) $data['language_id'],
                            ),
                        ),
                    ),
                ),

            Filter::make('unused')
                ->label(__('capell-layout-builder::table.widget_usage_unused'))
                ->query(self::applyUnusedFilter(...)),

            StatusFilter::make('status'),

            TrashedFilter::make(),
        ];
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    private static function whereUsedByLayout(Builder $query, int $layoutId): Builder
    {
        $layout = Layout::query()->find($layoutId);

        if (! $layout instanceof Layout || $layout->widgets === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('key', $layout->widgets);
    }

    /**
     * @param  Builder<Widget>  $query
     * @return Builder<Widget>
     */
    private static function applyUnusedFilter(Builder $query): Builder
    {
        if (! self::hasAuthoritativeLayoutUsage()) {
            return $query->whereRaw('1 = 0');
        }

        $model = $query->getModel();
        $countedWidgets = Widget::query()
            ->withTrashed()
            ->select((new Widget)->qualifyColumn('id'))
            /** @phpstan-ignore-next-line Widget exposes this local scope through Eloquent. */
            ->withLayoutsCount(LayoutResource::getEloquentQuery());

        return $query->whereIn(
            $model->qualifyColumn($model->getKeyName()),
            function (BaseQueryBuilder $query) use ($countedWidgets): void {
                $query->fromSub($countedWidgets->toBase(), 'widget_layout_counts')
                    ->select('widget_layout_counts.id')
                    ->where('widget_layout_counts.layouts_count', 0);
            },
        );
    }

    private static function deletionImpactDescription(Widget $record): string
    {
        $impact = BuildWidgetDeletionImpactAction::run($record);

        if ($impact->layouts === 0 && $impact->isAuthoritative) {
            return (string) __('capell-layout-builder::message.widget_delete_impact_unused');
        }

        if ($impact->layouts === 0) {
            return (string) __('capell-layout-builder::message.widget_delete_impact_no_tracked_uses');
        }

        return (string) trans_choice(
            'capell-layout-builder::message.widget_delete_impact_layouts',
            $impact->layouts,
            ['count' => $impact->layouts],
        );
    }

    private static function hasAuthoritativeLayoutUsage(): bool
    {
        $actor = auth()->user();

        return $actor instanceof Authenticatable
            && SiteScope::isGlobalActor($actor)
            && LayoutResource::canViewAny();
    }
}
