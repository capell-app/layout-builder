<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Layouts\Tables;

use Capell\Admin\Enums\FilamentColorEnum;
use Capell\Admin\Filament\Components\Tables\Columns\ImageColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Actions\GetLayoutPreviewImageUrlAction;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewMetaKey;
use Filament\Actions\Action;
use Filament\Infolists\Components\ViewEntry;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\Layout\Component;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Override;

class LayoutsTable extends \Capell\Admin\Filament\Resources\Layouts\Tables\LayoutsTable
{
    #[Override]
    protected static function getTableQueryModifier(Builder $query): Builder
    {
        return parent::getTableQueryModifier($query);
    }

    #[Override]
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
                            'widgets' => self::widgetWidgetsForLayout($record),
                        ],
                    ),
            ]);
    }

    #[Override]
    protected static function getTableColumns(): array
    {
        $columns = parent::getTableColumns();

        $nameColumnIndex = array_search(
            NameColumn::class,
            array_map(fn (Column|Component $column): string => $column::class, $columns),
            true,
        );

        $usesCardLayout = collect($columns)->contains(fn (Column|Component $column): bool => $column instanceof View);

        if ($nameColumnIndex !== false && ! $usesCardLayout) {
            array_splice($columns, $nameColumnIndex + 1, 0, [
                TextColumn::make('layout_widgets')
                    ->label(__('capell-layout-builder::table.container_widgets'))
                    ->getStateUsing(fn (Layout $record): array => self::widgetWidgetsForLayout($record)
                        ->pluck('name')
                        ->all())
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
            array_map(fn (Column|Component $column): string => $column::class, $columns),
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

    #[Override]
    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('widget_key')
                ->label(__('capell-layout-builder::form.widget'))
                ->options(fn () => Widget::query()
                    ->pluck('name', 'key')
                    ->all())
                ->indicateUsing(function (array $state): array {
                    $indicators = [];

                    if (isset($state['value']) && $state['value'] !== '') {
                        $indicators['widget_key'] = __(
                            'capell-layout-builder::filter.widget',
                            ['search' => Widget::query()->where('key', $state['value'])->value('name')],
                        );
                    }

                    return $indicators;
                })
                ->modifyQueryUsing(
                    fn (Builder $query, array $state) => $query->when(
                        isset($state['value']) && $state['value'] !== '',
                        fn (Builder $query): Builder => self::whereContainsWidgetKey($query, (string) $state['value']),
                    ),
                ),
            ...parent::getTableFilters(),
        ];
    }

    /**
     * @return Collection<int, Widget>
     */
    private static function widgetWidgetsForLayout(Layout $layout): Collection
    {
        $widgetKeys = $layout->widgets;

        if ($widgetKeys === []) {
            return collect();
        }

        return Widget::query()
            ->whereIn('key', $widgetKeys)
            ->get()
            ->sortBy(fn (Widget $widget): int => array_search($widget->key, $widgetKeys, true) ?: 0)
            ->values();
    }

    /**
     * @param  Builder<Layout>  $query
     * @return Builder<Layout>
     */
    private static function whereContainsWidgetKey(Builder $query, string $widgetKey): Builder
    {
        $escapedWidgetKey = addcslashes($widgetKey, '\%_');

        return $query->where(function (Builder $query) use ($escapedWidgetKey): void {
            $query
                ->where('containers', 'like', '%"widget_key":"' . $escapedWidgetKey . '"%')
                ->orWhere('containers', 'like', '%"widgets":["' . $escapedWidgetKey . '"%')
                ->orWhere('containers', 'like', '%,"' . $escapedWidgetKey . '"%');
        });
    }
}
