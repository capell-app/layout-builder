<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources;

use Awcodes\FilamentBadgeableColumn\Components\Badge;
use Capell\Admin\Filament\Components\Forms\TypeSchema;
use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\ImageColumn;
use Capell\Admin\Filament\Components\Tables\Columns\LanguagesColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\StatusColumn;
use Capell\Admin\Filament\Components\Tables\Filters\StatusFilter;
use Capell\Admin\Filament\Components\Tables\Filters\TextFilter;
use Capell\Admin\Filament\Resources\LayoutResource;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\LayoutModelEnum;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\SchemaEnum;
use Capell\Layout\Filament\Components\Forms\Widget\CreateWidgetDetailsSchema;
use Capell\Layout\Filament\Resources\WidgetResource\Pages;
use Capell\Layout\Filament\Resources\WidgetResource\RelationManagers;
use Capell\Layout\Filament\Schemas\Widget\DefaultWidgetSchema;
use Capell\Layout\Models\Widget;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class WidgetResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    public static function getResourceType(): string
    {
        return 'Widget';
    }

    /**
     * @return class-string<Widget>
     */
    public static function getModel(): string
    {
        return CapellCore::getModel(LayoutModelEnum::Widget->name);
    }

    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-admin::navigation.widgets'));
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_layouts'));
    }

    public static function getNavigationBadge(): ?string
    {
        if (! config('capell-admin.resources.widget.navigation_badge')) {
            return null;
        }

        return number_format(static::getModel()::count());
    }

    public static function getPluralModelLabel(): string
    {
        return __('capell-admin::generic.widgets');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'key', 'translations.title', 'meta->component', 'meta->file', 'meta->component_item'];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getFormSchema(Forms\Form $form): array
    {
        return [
            ...CreateWidgetDetailsSchema::make($form),
            TypeSchema::make()
                ->schema(
                    function (Forms\Get $get, TypeSchema $component) use ($form): array {
                        $typeId = $get('type_id');

                        $type = $typeId ? CapellCore::getModel(ModelEnum::Type)::find($typeId, ['admin']) : null;

                        $adminSchema = $type->admin['schema'] ?? DefaultWidgetSchema::getKey();

                        return $component->getSchema($form, SchemaEnum::Widget->name, $adminSchema);
                    }
                ),
        ];
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema(self::getFormSchema($form));
    }

    public static function table(Table $table): Table
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
                    ->withLayoutsCount()
                    ->leftJoin('types', 'widgets.type_id', '=', 'types.id')
                    ->withoutGlobalScopes([
                        SoftDeletingScope::class,
                    ])
            )
            ->columns(self::getTableColumns())
            ->filters(self::getTableFilters())
            ->recordClasses(fn (Widget $record): ?string => match (true) {
                (bool) $record->deleted_at => 'table-row-warning',
                default => null,
            })
            ->actions([
                EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    ReplicateAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\WidgetAssetsRelationManager::class,
            RelationManagers\LayoutsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWidgets::route('/'),
            'edit' => Pages\EditWidget::route('/{record}/edit'),
            'create' => Pages\CreateWidget::route('/create'),
        ];
    }

    private static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            NameColumn::make('name')
                ->description(fn (Widget $record): ?string => $record->admin['notes'] ?? null)
                ->suffixBadges([
                    Badge::make('type.name')
                        ->label(fn (Widget $record): ?string => $record->type?->name)
                        ->color('gray'),
                ])
                ->searchable([
                    'widgets.name',
                    'widgets.admin->notes',
                    'widgets.meta->component',
                    'widgets.meta->component_item',
                    'widgets.meta->file_view',
                    'types.name',
                    'types.admin->notes',
                    'types.meta->component',
                ]),
            ImageColumn::make('meta.image')
                ->toggleable(isToggledHiddenByDefault: true),
            LanguagesColumn::make('translations.language'),
            Tables\Columns\TextColumn::make('translation.contents')
                ->label(__('capell-admin::table.content'))
                ->sortable()
                ->searchable()
                ->limit(200)
                ->wrap()
                ->color('gray')
                ->weight(FontWeight::Medium)
                ->html()
                ->listWithLineBreaks()
                ->formatStateUsing(
                    fn (Pages\ListWidgets $livewire, Tables\Columns\TextColumn $column, Widget $record): string => Str::limit(
                        $record->translation->title ?? '',
                        $column->getCharacterLimit(),
                        $column->getCharacterLimitEnd()
                    )
                )
                ->description(function (Pages\ListWidgets $livewire, Tables\Columns\TextColumn $column, Widget $record): ?HtmlString {
                    if (! $record->translation?->contents) {
                        return null;
                    }

                    $contents = '';

                    foreach ($record->translation->contents as $content) {
                        if (! isset($content['data']['content'])) {
                            continue;
                        }

                        $contents .= strip_tags((string) $content['data']['content']);

                        if (Str::length($contents) >= $column->getCharacterLimit()) {
                            break;
                        }
                    }

                    return new HtmlString(Str::limit($contents, $column->getCharacterLimit(), $column->getCharacterLimitEnd()));
                })
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('key')
                ->label(__('capell-admin::table.key'))
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->searchable('widgets.key'),
            Tables\Columns\TextColumn::make('meta.component')
                ->label(__('capell-admin::table.component'))
                ->searchable(query: function (Builder $query, $search): Builder {
                    /** @var Connection $databaseConnection */
                    $databaseConnection = $query->getConnection();

                    $searchOperator = match ($databaseConnection->getDriverName()) {
                        'pgsql' => 'ilike',
                        default => 'like',
                    };

                    return $query->where(
                        fn (Builder $query): Builder => $query
                            ->where('widgets.meta->component', $searchOperator, sprintf('%%%s%%', $search))
                            ->orWhere('widgets.meta->file', $searchOperator, sprintf('%%%s%%', $search))
                            ->orWhere('widgets.meta->component_item', $searchOperator, sprintf('%%%s%%', $search))
                    );
                })
                ->size('xs')
                ->color('gray')
                ->formatStateUsing(function (Widget $record): ?HtmlString {
                    $components = [
                        __('capell-admin::form.component') => $record->meta['component'] ?? '',
                        __('capell-admin::form.file') => $record->meta['file'] ?? '',
                        __('capell-admin::form.component_item') => $record->meta['component_item'] ?? '',
                    ];

                    $components = array_filter($components);

                    if ($components === []) {
                        return null;
                    }

                    array_walk($components, fn ($value, $key): string => sprintf('%s: %s', $key, $value));

                    return new HtmlString(implode('<br />', $components));
                })
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('widget_resources_count')
                ->label(__('capell-admin::table.total_resources'))
                ->counts('widgetAssets')
                ->sortable()
                ->numeric()
                ->weight(FontWeight::SemiBold)
                ->alignCenter()
                ->toggleable(),
            Tables\Columns\TextColumn::make('layouts_count')
                ->label(__('capell-admin::table.total_layouts'))
                ->sortable()
                ->numeric()
                ->weight(FontWeight::SemiBold)
                ->alignCenter()
                ->toggleable()
                ->disabledClick()
                ->formatStateUsing(fn (Widget $record, $state): HtmlString => new HtmlString(Blade::render('capell-admin::components.tables.url', [
                    'state' => $state,
                    'url' => LayoutResource::getUrl('index', ['tableFilters[widget_id][value]' => $record->key]),
                ]))),
            StatusColumn::make('status'),
            DateColumn::make('created_at'),
            DateColumn::make('updated_at'),
            DateColumn::make('deleted_at'),
        ];
    }

    private static function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('type_id')
                ->label(__('capell-admin::form.widget_type'))
                ->relationship(
                    name: 'type',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query): Builder => $query->where(
                        'type',
                        LayoutTypeEnum::Widget
                    )
                ),

            Tables\Filters\SelectFilter::make('layout_id')
                ->label(__('capell-admin::form.layout'))
                ->relationship(
                    name: 'layouts',
                    titleAttribute: 'name'
                ),

            TextFilter::make('file')
                ->label(__('capell-admin::form.component'))
                ->query(function (TextFilter $filter, $data, Builder $query): void {
                    if (! empty($data['clause'])) {
                        $columns = ['meta->component', 'meta->file', 'meta->component_item'];
                        $filter->applyQueryClause($query, $columns, $data['clause'], $data);
                    }
                }),
            Tables\Filters\Filter::make('filter')
                ->form([
                    Forms\Components\Select::make('language_id')
                        ->label(__('capell-admin::table.language'))
                        ->options(function (): array {
                            /* @var class-string<\Capell\Core\Models\Language> $model */
                            $model = CapellCore::getModel(ModelEnum::Language);

                            return $model::ordered()
                                ->pluck('name', 'id')
                                ->toArray();
                        }),
                ])
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if (! empty($data['language_id'])) {
                        $indicators['language_id'] = __(
                            'capell-admin::filter.language',
                            ['search' => CapellCore::getModel(ModelEnum::Language)::find($data['language_id'], 'name')?->name]
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
                                (int) $data['language_id']
                            )
                        )
                    )
                ),

            StatusFilter::make('status'),

            Tables\Filters\TrashedFilter::make(),
        ];
    }
}
