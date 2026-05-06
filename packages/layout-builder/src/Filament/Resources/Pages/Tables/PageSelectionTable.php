<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Pages\Tables;

use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\SiteColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Core\Models\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PageSelectionTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        /* @var class-string<\Capell\Core\Models\Page> $model */
        $model = Page::class;

        return $table
            ->query(fn (): Builder => $model::query()->with([
                'ancestors.type',
                'site',
                'translation',
                'translations.language',
                'type',
            ]))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                IdentifierColumn::make('id'),
                PageNameColumn::make('name'),
                SiteColumn::make('site.name'),
            ])
            ->filters([
                SelectFilter::make('site_id')
                    ->label(__('capell-admin::form.site'))
                    ->relationship(
                        name: 'site',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->ordered(),
                    ),
            ])
            ->modifyQueryUsing(function (Builder $query, HasTable $livewire): Builder {
                $arguments = $livewire->getTableArguments();
                $excludeIds = $arguments['excludeIds'] ?? [];
                $pageId = $arguments['pageId'] ?? null;

                return $query
                    ->when($excludeIds !== [], fn (Builder $query) => $query->whereNotIn('id', $excludeIds))
                    ->when($pageId, fn (Builder $query) => $query->whereKeyNot($pageId));
            });
    }
}
