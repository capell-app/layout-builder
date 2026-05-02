<?php

declare(strict_types=1);

namespace Capell\Tags\Filament\Resources\Tags\Tables;

use Capell\Admin\Enums\FilamentColorEnum;
use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\SiteColumn;
use Capell\Admin\Filament\Components\Tables\Columns\StatusIconColumn;
use Capell\Admin\Filament\Components\Tables\Filters\StatusFilter;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Core\Models\Language;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TagsTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query->with(['site'])
                    ->select('*')
                    ->withTranslatedLocales('name'),
            )
            ->defaultSort('name')
            ->columns(static::getTableColumns())
            ->filters([
                SelectFilter::make('site_id')
                    ->label(__('capell-admin::form.site'))
                    ->relationship(name: 'site', titleAttribute: 'name'),
                TernaryFilter::make('featured')
                    ->label(__('capell-mosaic::table.featured'))
                    ->trueLabel(__('capell-admin::generic.yes'))
                    ->falseLabel(__('capell-admin::generic.no'))
                    ->placeholder(__('capell-admin::generic.all')),
                StatusFilter::make('status'),
            ])
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
            ]);
    }

    protected static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            NameColumn::make('name')
                ->searchable(
                    query: function (TextColumn $column, Builder $query, string $search): Builder {
                        if ($search === '' || $search === '0') {
                            return $query;
                        }

                        $locals = Language::query()->pluck('code')->all();

                        return $query->whereJsonContainsLocales($column->getName(), $locals, sprintf('%%%s%%', $search), 'like');
                    },
                ),
            TextColumn::make('slug')
                ->label(__('capell-tags::table.slug'))
                ->searchable()
                ->sortable()
                ->color(FilamentColorEnum::LightGray->value)
                ->toggleable(),
            TextColumn::make('translated_locales')
                ->label(__('capell-admin::table.languages'))
                ->toggleable(isToggledHiddenByDefault: true)
                ->view('capell-admin::components.tables.columns.locale-flags'),
            SiteColumn::make('site.name'),
            TextColumn::make('taggables_count')
                ->label(__('capell-tags::table.total_taggables'))
                ->counts('taggables')
                ->sortable()
                ->alignRight()
                ->numeric()
                ->toggleable(),
            ToggleColumn::make('featured')
                ->label(__('capell-mosaic::table.featured'))
                ->alignCenter()
                ->toggleable(),
            StatusIconColumn::make('status'),
            DateColumn::make('created_at'),
            DateColumn::make('updated_at'),
        ];
    }
}
