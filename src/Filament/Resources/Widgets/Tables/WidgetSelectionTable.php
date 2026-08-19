<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Widgets\Tables;

use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\LayoutBuilder\Models\Widget;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WidgetSelectionTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        WidgetsTable::configure($table);

        return $table->query(function (): Builder {
            /* @var class-string<\Capell\LayoutBuilder\Models\Widget> $model */
            $model = Widget::class;

            return $model::query();
        })
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with('image')
                ->withCount('widgetAssets'))
            ->columns([
                View::make('capell-layout-builder::filament.resources.widgets.widget-card'),
                TextColumn::make('name')
                    ->hidden()
                    ->searchable([
                        'name',
                        'admin->notes',
                        'component',
                        'component_item',
                        'view_file',
                    ]),
                TextColumn::make('key')
                    ->hidden()
                    ->searchable()
                    ->sortable(),
            ])
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->recordClasses('capell-layout-builder-widget-selection-card-record')
            ->checkIfRecordIsSelectableUsing(fn (Widget $record): bool => $record->status === true);
    }
}
