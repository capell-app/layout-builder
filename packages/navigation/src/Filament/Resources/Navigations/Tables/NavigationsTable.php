<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Resources\Navigations\Tables;

use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\LanguageColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\SiteColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Navigation\Filament\Components\Tables\Columns\Navigation\NavigationItemsColumn;
use Capell\Navigation\Filament\Resources\Navigations\Pages\ListNavigations;
use Capell\Navigation\Models\Navigation;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NavigationsTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query->with([
                    'creator',
                    'editor',
                    'language',
                    'site',
                    'type',
                ])
                    ->where(
                        fn (BuilderContract $query): BuilderContract => $query->whereNull('site_id')
                            ->orWhereHas('site'),
                    )
                    ->withoutGlobalScopes([
                        SoftDeletingScope::class,
                    ]),
            )
            ->defaultSort('name')
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
            ->filtersFormColumns(3)
            ->recordActions([
                EditAction::make('edit'),
                ActionGroup::make([
                    ReplicateAction::make('replicate'),
                    DeleteAction::make('delete'),
                ])
                    ->color('gray'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make('delete'),
                ForceDeleteBulkAction::make('forceDelete'),
                RestoreBulkAction::make('restore'),
            ]);
    }

    protected static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            NameColumn::make('name')
                ->searchable()
                ->sortable()
                ->description(fn (Navigation $record): ?string => $record->key),
            NavigationItemsColumn::make('items'),
            TextColumn::make('items_count')
                ->label(__('capell-admin::table.total_items'))
                ->getStateUsing(fn (Navigation $record): int => $record->items !== null ? count($record->items) : 0)
                ->alignCenter()
                ->numeric()
                ->toggleable(),
            SiteColumn::make('site.name')
                ->hidden(
                    fn (ListNavigations $livewire): bool => (bool) $livewire->activeTab,
                ),
            LanguageColumn::make('language')
                ->hidden(
                    fn (ListNavigations $livewire): bool => isset($livewire->getTableFilterState('filter')['language_id']) && $livewire->getTableFilterState('filter')['language_id'] !== '',
                )
                ->toggleable(),
            TextColumn::make('meta.component')
                ->label(__('capell-admin::table.component'))
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
            DateColumn::make('created_at'),
            DateColumn::make('updated_at'),
            DateColumn::make('deleted_at'),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            Filter::make('filter')
                ->columns(['default' => 1, 'md' => 2, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    Select::make('site_id')
                        ->label(__('capell-admin::form.site'))
                        ->reactive()
                        ->options(function (): array {
                            /** @var class-string<Site> $model */
                            $model = Site::class;

                            return $model::getOptions()->toArray();
                        }),

                    Select::make('language_id')
                        ->label(__('capell-admin::table.language'))
                        ->options(function (Get $get): array {
                            /** @var class-string<Language> $model */
                            $model = Language::class;

                            return $model::query()
                                ->when(
                                    $get('data.site_id', true),
                                    fn (Builder $query, int $site_id): Builder => $query->whereHas(
                                        'sites',
                                        fn (BuilderContract $query): BuilderContract => $query->where('sites.id', $site_id),
                                    ),
                                )
                                ->ordered()
                                ->pluck('name', 'id')
                                ->toArray();
                        }),

                    Select::make('key')
                        ->label(__('capell-admin::table.key'))
                        ->options(function (Get $get): array {
                            /** @var class-string<Navigation> $navigation */
                            $navigation = Navigation::class;

                            return $navigation::query()->select(['key'])
                                ->when(
                                    $get('data.site_id', true),
                                    fn (Builder $query, int $site_id): Builder => $query->where('site_id', $site_id),
                                )
                                ->when(
                                    $get('data.language_id', true),
                                    fn (Builder $query, int $language_id): Builder => $query->where('language_id', $language_id),
                                )
                                ->groupBy('key')
                                ->orderBy('key')
                                ->pluck('key', 'key')
                                ->toArray();
                        }),
                ])
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if (isset($data['site_id']) && $data['site_id'] !== '') {
                        /** @var class-string<Site> $model */
                        $model = Site::class;

                        $indicators['site_id'] = __(
                            'capell-admin::filter.site',
                            ['search' => $model::query()->find($data['site_id'], 'name')?->name],
                        );
                    }

                    if (isset($data['language_id']) && $data['language_id'] !== '') {
                        /** @var class-string<Language> $model */
                        $model = Language::class;

                        $indicators['language_id'] = __(
                            'capell-admin::filter.language',
                            ['search' => $model::query()->find($data['language_id'], 'name')?->name],
                        );
                    }

                    if (isset($data['key']) && $data['key'] !== '') {
                        $indicators['key'] = __(
                            'capell-admin::filter.key',
                            ['search' => $data['key']],
                        );
                    }

                    return $indicators;
                })
                ->query(function (Builder $query, array $data): void {
                    $query->when($data['language_id'], fn (Builder $query) => $query->where('language_id', $data['language_id']))
                        ->when($data['site_id'], fn (Builder $query) => $query->where('site_id', $data['site_id']))
                        ->when($data['key'], fn (Builder $query) => $query->where('key', $data['key']));
                }),

            TrashedFilter::make(),
        ];
    }
}
