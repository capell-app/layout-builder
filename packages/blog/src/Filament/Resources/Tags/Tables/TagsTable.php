<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Tags\Tables;

use Capell\Admin\Enums\FilamentColorEnum;
use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\SiteColumn;
use Capell\Admin\Filament\Components\Tables\Columns\StatusIconColumn;
use Capell\Admin\Filament\Components\Tables\Filters\StatusFilter;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Blog\Models\Tag;
use Capell\Core\Models\Language;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

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
            ->columns(static::getTableColumns())
            ->filters([
                SelectFilter::make('site_id')
                    ->label(__('capell-admin::form.site'))
                    ->relationship(name: 'site', titleAttribute: 'name'),
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
                ->label(__('capell-layout::table.slug'))
                ->searchable()
                ->sortable()
                ->color(FilamentColorEnum::LightGray->value)
                ->toggleable(),
            TextColumn::make('translated_locales')
                ->label(__('capell-admin::table.languages'))
                ->toggleable(isToggledHiddenByDefault: true)
                ->view('capell-admin::components.tables.columns.locale-flags'),
            SiteColumn::make('site.name'),
            TextColumn::make('pages_count')
                ->label(__('capell-admin::table.total_pages'))
                ->counts('pages')
                ->sortable()
                ->alignCenter()
                ->numeric()
                ->disabledClick()
                ->toggleable()
                ->formatStateUsing(function (Tag $record, int $state): ?HtmlString {
                    if (blank($state)) {
                        return null;
                    }

                    $url = PageResource::getUrl('index', ['tableFilters[tags][value]' => $record->id]);

                    return new HtmlString(
                        Blade::render('capell-admin::components.tables.url', ['state' => $state, 'url' => $url]),
                    );
                }),
            IconColumn::make('featured')
                ->label(__('capell-layout::table.featured'))
                ->trueIcon('heroicon-o-star')
                ->falseIcon(false)
                ->color(fn (Tag $record): string => $record->featured ? 'primary' : 'gray')
                ->alignCenter()
                ->toggleable(),
            StatusIconColumn::make('status'),
            TextColumn::make('created_at')
                ->label(__('capell-admin::table.created_at'))
                ->sortable()
                ->since()
                ->size('sm')
                ->alignRight()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('updated_at')
                ->label(__('capell-admin::table.updated_at'))
                ->sortable()
                ->since()
                ->size('sm')
                ->alignRight()
                ->toggleable(),
        ];
    }
}
