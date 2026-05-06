<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Layouts\Tables;

use Capell\Admin\Enums\FilamentColorEnum;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Models\Widget;
use Filament\Actions\Action;
use Filament\Infolists\Components\ViewEntry;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class LayoutsTable extends \Capell\Admin\Filament\Resources\Layouts\Tables\LayoutsTable
{
    protected static function getTableQueryModifier(Builder $query): Builder
    {
        return parent::getTableQueryModifier($query)->with('layoutWidgets');
    }

    protected static function getTableActions(): array
    {
        return [
            self::getLayoutInfoAction(),
            ...parent::getTableActions(),
        ];
    }

    protected static function getLayoutInfoAction(): Action
    {
        return Action::make('info')
            ->label(__('capell-layout-builder::button.info'))
            ->icon('heroicon-o-information-circle')
            ->iconButton()
            ->color('info')
            ->schema(fn (Layout $record): array => [
                ViewEntry::make('widgets')
                    ->view(
                        'capell-layout-builder::components.infolists.entries.layout-widgets',
                        [
                            'widgets' => $record->layoutWidgets,
                        ],
                    ),
            ]);
    }

    protected static function getTableColumns(): array
    {
        $columns = parent::getTableColumns();

        $nameColumnIndex = array_search(
            NameColumn::class,
            array_map(fn (Column $column): string|false => $column::class, $columns),
            true,
        );

        if ($nameColumnIndex !== false) {
            array_splice($columns, $nameColumnIndex + 1, 0, [
                TextColumn::make('layoutWidgets.name')
                    ->label(__('capell-layout-builder::table.container_widgets'))
                    ->wrap()
                    ->color(FilamentColorEnum::LightGray->value)
                    ->bulleted()
                    ->limitList()
                    ->expandableLimitedList()
                    ->toggleable(),
            ]);
        }

        return $columns;
    }

    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('widget_key')
                ->label(__('capell-layout-builder::form.widget'))
                ->options(function () {
                    /** @var class-string<Widget> $model */
                    $model = Widget::class;

                    return $model::getOptions('key', 'name');
                })
                ->indicateUsing(function (array $state): array {
                    $indicators = [];

                    if (isset($state['value']) && $state['value'] !== '') {
                        /** @var class-string<Widget> $model */
                        $model = Widget::class;

                        $indicators['widget_key'] = __(
                            'capell-layout-builder::filter.widget',
                            ['search' => $model::query()->firstWhere('key', $state['value'], 'name')?->name],
                        );
                    }

                    return $indicators;
                })
                ->modifyQueryUsing(
                    fn (Builder $query, array $state) => $query->when(
                        isset($state['value']) && $state['value'] !== '',
                        fn (Builder $query) => $query->whereJsonContains('widgets', $state['value']),
                    ),
                ),
            ...parent::getTableFilters(),
        ];
    }
}
