<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Tables;

use Capell\Admin\Enums\FilamentColorEnum;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\LanguagesColumn;
use Capell\Admin\Filament\Components\Tables\Columns\MediaLibraryImageColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\StatusIconColumn;
use Capell\Admin\Filament\Components\Tables\Filters\StatusFilter;
use Capell\Admin\Filament\Components\Tables\Filters\TextFilter;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Filament\Resources\Widgets\Pages\ListWidgets;
use Capell\Layout\Models\Widget;
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
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
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
                    ->withLayoutsCount(),
            )
            ->columns(self::getTableColumns())
            ->filters(self::getTableFilters())
            ->recordClasses(fn (Widget $record): ?string => match (true) {
                (bool) $record->deleted_at => 'table-row-warning',
                default => null,
            })
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    ReplicateAction::make(),
                    DeleteAction::make(),
                ])
                    ->color('gray'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                ForceDeleteBulkAction::make(),
                RestoreBulkAction::make(),
            ]);
    }

    protected static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            NameColumn::make('name')
                ->searchable([
                    'name',
                    'admin->notes',
                    'meta->component',
                    'meta->component_item',
                    'meta->view_file',
                ]),
            TextColumn::make('type.name')
                ->label(__('capell-admin::table.type'))
                ->badge()
                ->searchable()
                ->sortable(),
            MediaLibraryImageColumn::make('meta.image')
                ->toggleable(isToggledHiddenByDefault: true),
            LanguagesColumn::make('translations.language'),
            TextColumn::make('translation.contents')
                ->label(__('capell-admin::table.content'))
                ->sortable()
                ->searchable()
                ->limit(200)
                ->wrap()
                ->color(FilamentColorEnum::LightGray->value)
                ->html()
                ->listWithLineBreaks()
                ->toggleable(isToggledHiddenByDefault: true)
                ->formatStateUsing(
                    fn (ListWidgets $livewire, TextColumn $column, Widget $record): string => Str::limit(
                        $record->translation->title ?? '',
                        $column->getCharacterLimit(),
                        $column->getCharacterLimitEnd(),
                    ),
                )
                ->description(function (ListWidgets $livewire, TextColumn $column, Widget $record): ?HtmlString {
                    if (! $record->translation?->content) {
                        return null;
                    }

                    return new HtmlString(
                        Str::limit(
                            $record->translation->content,
                            $column->getCharacterLimit(),
                            $column->getCharacterLimitEnd(),
                        ),
                    );
                }),
            TextColumn::make('key')
                ->label(__('capell-admin::table.key'))
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->searchable('key'),
            TextColumn::make('meta.component')
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
                            ->where('meta->component', $searchOperator, sprintf('%%%s%%', $search))
                            ->orWhere('meta->file', $searchOperator, sprintf('%%%s%%', $search))
                            ->orWhere('meta->component_item', $searchOperator, sprintf('%%%s%%', $search)),
                    );
                })
                ->size('xs')
                ->color(FilamentColorEnum::LightGray->value)
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

                    array_walk($components, fn ($value, string $key): string => sprintf('%s: %s', $key, $value));

                    return new HtmlString(implode('<br />', $components));
                })
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('widget_assets_count')
                ->label(__('capell-layout::table.total_assets'))
                ->counts('widgetAssets')
                ->sortable()
                ->alignCenter()
                ->numeric()
                ->toggleable(),
            TextColumn::make('layouts_count')
                ->label(__('capell-layout::table.total_layouts'))
                ->sortable()
                ->alignCenter()
                ->numeric()
                ->toggleable()
                ->disabledClick()
                ->formatStateUsing(
                    fn (Widget $record, $state): HtmlString => new HtmlString(
                        Blade::render(
                            'capell-admin::components.tables.url',
                            [
                                'state' => $state,
                                'url' => CapellAdmin::getResource(ResourceEnum::Layout)::getUrl('index', ['tableFilters[widget_id][value]' => $record->key]),
                            ],
                        ),
                    ),
                ),
            StatusIconColumn::make('status'),
            DateColumn::make('created_at'),
            DateColumn::make('updated_at'),
            DateColumn::make('deleted_at'),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('type_id')
                ->label(__('capell-layout::form.widget_type'))
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
                ->relationship(
                    name: 'layouts',
                    titleAttribute: 'name',
                ),

            TextFilter::make('file')
                ->label(__('capell-admin::form.component'))
                ->query(function (TextFilter $filter, array $data, Builder $query): void {
                    if (! empty($data['clause'])) {
                        $columns = ['meta->component', 'meta->file', 'meta->component_item'];
                        $filter->applyQueryClause($query, $columns, $data['clause'], $data);
                    }
                }),
            Filter::make('filter')
                ->schema([
                    Select::make('language_id')
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
                            ['search' => CapellCore::getModel(ModelEnum::Language)::find($data['language_id'], 'name')?->name],
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

            StatusFilter::make('status'),

            TrashedFilter::make(),
        ];
    }
}
