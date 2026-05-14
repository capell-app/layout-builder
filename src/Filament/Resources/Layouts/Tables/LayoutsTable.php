<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Layouts\Tables;

use Capell\Admin\Enums\FilamentColorEnum;
use Capell\Admin\Filament\Components\Tables\Columns\ImageColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Widget;
use Capell\LayoutBuilder\Actions\GetLayoutPreviewImageUrlAction;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewMetaKey;
use Filament\Actions\Action;
use Filament\Infolists\Components\ViewEntry;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\Layout\Component;
use Filament\Tables\Columns\Layout\View;
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
                            'widgets' => $record->getRelationValue('layoutWidgets'),
                        ],
                    ),
            ]);
    }

    protected static function getTableColumns(): array
    {
        $columns = parent::getTableColumns();

        $nameColumnIndex = array_search(
            NameColumn::class,
            array_map(fn (Column|Component $column): string|false => $column::class, $columns),
            true,
        );

        $usesCardLayout = collect($columns)->contains(fn (Column|Component $column): bool => $column instanceof View);

        if ($nameColumnIndex !== false && ! $usesCardLayout) {
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

        foreach ($columns as $column) {
            if (! $column instanceof ImageColumn) {
                continue;
            }

            if ($column->getName() !== 'admin.image') {
                continue;
            }

            $column->getStateUsing(fn (Layout $record): ?string => GetLayoutPreviewImageUrlAction::run($record));
        }

        $imageColumnIndex = array_search(
            ImageColumn::class,
            array_map(fn (Column|Component $column): string|false => $column::class, $columns),
            true,
        );

        if ($imageColumnIndex !== false && ! $usesCardLayout) {
            array_splice($columns, $imageColumnIndex + 1, 0, [
                TextColumn::make('admin.' . LayoutPreviewMetaKey::STATUS)
                    ->label(__('capell-layout-builder::table.generated_preview'))
                    ->badge()
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
