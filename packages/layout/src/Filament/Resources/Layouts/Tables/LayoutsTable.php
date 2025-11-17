<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Layouts\Tables;

use Capell\Admin\Enums\FilamentColorEnum;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Layout\Enums\ModelEnum;
use Filament\Actions\Action;
use Filament\Infolists\Components\ViewEntry;
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
            ->label(__('capell-admin::button.info'))
            ->icon('heroicon-o-information-circle')
            ->iconButton()
            ->color('info')
            ->schema(fn (Layout $record): array => [
                ViewEntry::make('widgets')
                    ->view(
                        'capell-layout::components.infolists.entries.layout-widgets',
                        [
                            'widgets' => $record->layoutWidgets,
                        ],
                    ),
            ]);
    }

    protected static function getTableColumns(): array
    {
        $columns = parent::getTableColumns();

        $nameColumnIndex = array_search(NameColumn::class, array_map(fn ($col): string|false => $col::class, $columns), true);
        if ($nameColumnIndex !== false) {
            array_splice($columns, $nameColumnIndex + 1, 0, [
                TextColumn::make('layoutWidgets.name')
                    ->label(__('capell-admin::table.container_widgets'))
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
                ->label(__('capell-admin::form.widget'))
                ->options(fn () => CapellCore::getModel(ModelEnum::Widget->name)::getOptions('key', 'name'))
                ->indicateUsing(function (array $state): array {
                    $indicators = [];

                    if (! empty($state['value'])) {
                        $indicators['widget_key'] = __(
                            'capell-admin::filter.widget',
                            ['search' => CapellCore::getModel(ModelEnum::Widget->name)::firstWhere('key', $state['value'], 'name')?->name],
                        );
                    }

                    return $indicators;
                })
                ->modifyQueryUsing(
                    fn (Builder $query, $state) => $query->unless(
                        empty($state['value']),
                        fn (Builder $query) => $query->whereJsonContains('widgets', $state['value']),
                    ),
                ),
            ...parent::getTableFilters(),
        ];
    }
}
