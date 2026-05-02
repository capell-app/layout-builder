<?php

declare(strict_types=1);

namespace Capell\Mosaic\Livewire\Filament\Concerns;

use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Models\WidgetAsset;
use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;

trait ManagesWidgets
{
    public function addWidgetToContainer(Widget $widget, string $containerKey): int
    {
        $this->assertCanUpdateLayout();

        $occurrence = $this->getLastContainerWidgetOccurrence($containerKey, $widget->key) + 1;

        $this->containers[$containerKey]['widgets'][] = [
            'widget_key' => $widget->key,
            'occurrence' => $occurrence,
        ];

        $index = array_key_last($this->containers[$containerKey]['widgets']);

        $this->containerWidgets[$containerKey][$index] = $widget;

        $this->assets[$containerKey][$index] = [];

        return $index;
    }

    public function addWidgetToContainerAtPosition(Widget $widget, string $containerKey, ?int $position = null): int
    {
        $widgetIndex = $this->addWidgetToContainer($widget, $containerKey);

        if ($position === null || $position >= $widgetIndex) {
            return $widgetIndex;
        }

        $position = max(0, $position);

        $this->insertContainerWidgetAtPosition($containerKey, $widgetIndex, $position);

        return $position;
    }

    public function reorderWidgets(string $containerKey, string $containerWidgetIndex, int $widgetIndex): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        if (str_starts_with($containerWidgetIndex, 'palette.')) {
            $this->addPaletteWidgetToContainer(
                widgetId: (int) str($containerWidgetIndex)->after('palette.')->toString(),
                containerKey: $containerKey,
                position: $widgetIndex,
            );

            return;
        }

        [$originalContainer, $originalIndex] = explode('.', $containerWidgetIndex);

        $this->moveContainerWidgetAssets($originalContainer, (int) $originalIndex, $containerKey, $widgetIndex);

        $this->moveContainerWidget($originalContainer, (int) $originalIndex, $containerKey, $widgetIndex);

        $this->layoutUpdated();
    }

    protected function duplicateWidget(string $containerKey, int $originalIndex, bool $withAssets = true): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        $containerWidget = $this->containers[$containerKey]['widgets'][$originalIndex];

        $containerWidget['occurrence'] = $this->getLastContainerWidgetOccurrence($containerKey, $containerWidget['widget_key']) + 1;

        $this->containers[$containerKey]['widgets'][] = $containerWidget;

        $this->containerWidgets[$containerKey][] = $this->getContainerWidget($containerKey, $originalIndex);
        $widgetIndex = array_key_last($this->containerWidgets[$containerKey]);

        if ($withAssets) {
            $this->assets[$containerKey][$widgetIndex] = $this->assets[$containerKey][$originalIndex];
        }

        $this->layoutUpdated();
    }

    protected function removeWidget(string $containerKey, int $widgetIndex): void
    {
        $this->assertCanUpdateLayout();

        if (isset($this->containers[$containerKey]['widgets'][$widgetIndex])) {
            unset($this->containers[$containerKey]['widgets'][$widgetIndex]);
            $this->containers[$containerKey]['widgets'] = array_values($this->containers[$containerKey]['widgets']);
        }

        if (isset($this->containerWidgets[$containerKey][$widgetIndex])) {
            unset($this->containerWidgets[$containerKey][$widgetIndex]);
            $this->containerWidgets[$containerKey] = array_values($this->containerWidgets[$containerKey]);
        }

        if (isset($this->assets[$containerKey][$widgetIndex])) {
            unset($this->assets[$containerKey][$widgetIndex]);
            $this->assets[$containerKey] = array_values($this->assets[$containerKey]);
        }

        if (isset($this->selectedRecords[$containerKey][$widgetIndex])) {
            unset($this->selectedRecords[$containerKey][$widgetIndex]);
            $this->selectedRecords[$containerKey] = array_values($this->selectedRecords[$containerKey]);
        }

        $this->layoutUpdated();
    }

    protected function moveContainerWidget(string $originalContainer, int $originalIndex, string $containerKey, int $widgetIndex): void
    {
        $widget = $this->getContainerWidget($originalContainer, $originalIndex);

        $containerWidget = $this->containers[$originalContainer]['widgets'][$originalIndex];

        if ($originalContainer !== $containerKey) {
            $containerWidget['occurrence'] = $this->getLastContainerWidgetOccurrence(
                containerKey: $containerKey,
                widgetKey: $containerWidget['widget_key'],
                widgets: $this->containers[$containerKey]['widgets'],
            ) + 1;
        }

        $widgets = $this->containers[$originalContainer]['widgets'];

        unset($widgets[$originalIndex]);

        $this->containers[$originalContainer]['widgets'] = array_values($widgets);

        $widgets = $this->containers[$containerKey]['widgets'];
        $widgets = array_merge(array_slice($widgets, 0, $widgetIndex), [$containerWidget], array_slice($widgets, $widgetIndex));
        $this->containers[$containerKey]['widgets'] = $widgets;

        if ($containerKey !== $originalContainer) {
            unset($this->containerWidgets[$originalContainer][$originalIndex]);
            $this->containerWidgets[$originalContainer] = array_values($this->containerWidgets[$originalContainer]);
        }

        $containerWidgets = $this->containerWidgets[$containerKey] ?? [];
        $containerWidgets = array_merge(array_slice($containerWidgets, 0, $widgetIndex), [$widget], array_slice($containerWidgets, $widgetIndex));
        $this->containerWidgets[$containerKey] = $containerWidgets;

        $originalContainerWidgetAssets = $this->originalAssets[$originalContainer][$originalIndex] ?? [];
        unset($this->originalAssets[$originalContainer][$originalIndex]);
        $this->originalAssets[$containerKey][$widgetIndex] = $originalContainerWidgetAssets;

        $this->updatePageAssets($containerKey, $widgetIndex);
    }

    protected function insertContainerWidgetAtPosition(string $containerKey, int $originalIndex, int $position): void
    {
        if (isset($this->containers[$containerKey]['widgets'][$originalIndex])) {
            $this->containers[$containerKey]['widgets'] = $this->insertArrayItemAtPosition(
                $this->containers[$containerKey]['widgets'],
                $originalIndex,
                $position,
            );
        }

        foreach (['containerWidgets', 'assets', 'originalAssets', 'selectedRecords'] as $property) {
            if (! isset($this->{$property}[$containerKey][$originalIndex])) {
                continue;
            }

            $this->{$property}[$containerKey] = $this->insertArrayItemAtPosition(
                $this->{$property}[$containerKey],
                $originalIndex,
                $position,
            );
        }

        $this->updatePageAssets($containerKey, $position);
    }

    protected function insertArrayItemAtPosition(array $items, int $originalIndex, int $position): array
    {
        $item = $items[$originalIndex];

        unset($items[$originalIndex]);

        $items = array_values($items);

        return array_merge(array_slice($items, 0, $position), [$item], array_slice($items, $position));
    }

    protected function editLayoutWidget(string $containerKey, int $widgetIndex, array $data): void
    {
        $this->ensureLoaded();

        $this->containers[$containerKey]['widgets'][$widgetIndex]['meta'] = array_merge(
            $this->containers[$containerKey]['widgets'][$widgetIndex]['meta'] ?? [],
            $data,
        );

        $this->layoutUpdated();
    }

    protected function getContainerWidget(string $containerKey, int $widgetIndex): Widget
    {
        if (! isset($this->containerWidgets[$containerKey][$widgetIndex])) {
            $this->ensureLoaded();
        }

        if (! isset($this->containerWidgets[$containerKey][$widgetIndex])) {
            $widget = $this->loadWidget($containerKey, $widgetIndex, withAssets: false);

            $assets = $this->loadWidgetAssetsFor($widget, $containerKey, $widgetIndex);

            $widget->setRelation('assets', $assets);
        }

        return $this->containerWidgets[$containerKey][$widgetIndex];
    }

    protected function getContainerWidgetKeys(): array
    {
        return collect($this->containers)
            ->pluck('widgets.*.widget_key')
            ->flatten()
            ->unique()
            ->toArray();
    }

    protected function getContainerWidgetOccurrence(string $containerKey, int $widgetIndex): int
    {
        return (int) ($this->containers[$containerKey]['widgets'][$widgetIndex]['occurrence'] ?? 1);
    }

    protected function getLastContainerWidgetOccurrence(string $containerKey, string $widgetKey, ?int $compareIndex = null, ?array $widgets = null): int
    {
        if ($widgets === null || $widgets === []) {
            $widgets = $this->containers[$containerKey]['widgets'];
        }

        $occurrence = 1;

        foreach ($widgets as $widgetIndex => $widget) {
            if ($compareIndex !== null && $widgetIndex === $compareIndex) {
                return $occurrence;
            }

            if ($widget['widget_key'] === $widgetKey) {
                $occurrence++;
            }
        }

        return $occurrence;
    }

    protected function getContainerWidgetConfigurator(string $containerKey, int $widgetIndex): ?string
    {
        return $this->getContainerWidget($containerKey, $widgetIndex)?->type->admin['layout_widget_configurator']
            ?? null;
    }

    protected function loadWidget(string $containerKey, int $widgetIndex, bool $withAssets = true): Widget
    {
        $container = $this->containers[$containerKey] ?? null;

        throw_if($container === null || ! isset($container['widgets'][$widgetIndex]), Exception::class, 'Container widget not found for container: ' . $containerKey . ' index: ' . $widgetIndex);

        $containerWidget = $container['widgets'][$widgetIndex];
        $widgetKey = $containerWidget['widget_key'];
        $occurrence = $containerWidget['occurrence'] ?? 1;

        $widget = $this->getWidget($widgetKey);

        if ($withAssets) {
            $widget->setRelation('assets', $this->loadWidgetAssets($widget, $containerKey, $occurrence));
        }

        $this->containerWidgets[$containerKey][$widgetIndex] = $widget;

        return $widget;
    }

    protected function setupContainerWidgets(string $containerKey, array $allWidgets, ?array $allWidgetAssets = null): void
    {
        $container = $this->containers[$containerKey];

        $widgetOccurrences = [];

        foreach ($container['widgets'] as $widgetIndex => $containerWidget) {
            $widgetKey = $containerWidget['widget_key'];
            $oldContainerKey = $containerWidget['old_container'] ?? null;

            throw_unless(isset($allWidgets[$widgetKey]), Exception::class, 'Widget not found for key: ' . $widgetKey);

            /** @var Widget $widget */
            $widget = clone $allWidgets[$widgetKey];

            if (! isset($widgetOccurrences[$widgetKey])) {
                $widgetOccurrences[$widgetKey] = 1;
            } else {
                $widgetOccurrences[$widgetKey]++;
            }

            $widgetOccurrence = $widgetOccurrences[$widgetKey];

            $this->containers[$containerKey]['widgets'][$widgetIndex]['occurrence'] = $widgetOccurrence;

            if ($allWidgetAssets !== null) {
                $assets = $allWidgetAssets[$containerKey][$widgetIndex] ?? new Collection;
            } elseif ($widget->relationLoaded('assets')) {
                $assets = $widget->assets;
            } else {
                $assets = $this->loadWidgetAssets($widget, $containerKey, $widgetOccurrence);
            }

            $widget->setRelation(
                'assets',
                $this->filterContainerWidgetAssets($assets, $oldContainerKey ?? $containerKey, $widgetOccurrence),
            );

            $this->containerWidgets[$containerKey][$widgetIndex] = $widget;

            $this->assets[$containerKey][$widgetIndex] = $this->mapWidgetAssets($widget, $containerKey, $oldContainerKey);

            $this->updatePageAssets($containerKey, $widgetIndex);
        }
    }

    /**
     * @throws Exception
     */
    protected function getWidget(int|string $id, bool $withRelations = true): Widget
    {
        $query = $this->getWidgetQuery(withRelations: $withRelations);

        if (is_numeric($id)) {
            $query->whereKey($id);
        } else {
            $query->where('key', $id);
        }

        /** @var Widget|null $widget */
        $widget = $query->first();

        throw_unless($widget, Exception::class, sprintf("Unable to find '%s' widget", (string) $id));

        return $widget;
    }

    /**
     * @return EloquentBuilder<Widget>
     */
    protected function getWidgetQuery(bool $withRelations = true): EloquentBuilder
    {
        /** @var class-string<Widget> $model */
        $model = Widget::class;

        return $model::query()
            ->when(
                $withRelations,
                fn (EloquentBuilder $query) => $query->withCount([
                    'layouts',
                    'widgetPageAssets as page_assets_count' => fn (EloquentBuilder $query): EloquentBuilder => $query->distinct(['pageable_id', 'pageable_type'])
                        ->when(
                            $this->inPageContext(),
                            fn (EloquentBuilder $query) => $query->where([
                                'pageable_type' => $this->page->getMorphClass(),
                                'pageable_id' => $this->page->getKey(),
                            ]),
                        ),
                ])
                    ->with([
                        'type',
                        'backgroundImage',
                        'image',
                        'translation' => fn (BuilderContract $query): BuilderContract => $query->orderBy('language_id'),
                    ]),
            );
    }

    protected function preloadAllWidgets(bool $withAssets = true): array
    {
        $widgetKeys = $this->getContainerWidgetKeys();

        if ($widgetKeys === []) {
            return [];
        }

        $allWidgetAssets = $this->getWidgetQuery()
            ->whereIn('key', $widgetKeys)
            ->when(
                $withAssets,
                fn (EloquentBuilder $query): EloquentBuilder => $query->with([
                    'assets' => fn (BuilderContract $query): BuilderContract => $query->when(
                        $this->page,
                        fn (EloquentBuilder $query): EloquentBuilder => $query->where(
                            fn (EloquentBuilder $query): EloquentBuilder => $query->where([
                                'pageable_id' => $this->page->getKey(),
                                'pageable_type' => $this->page->getMorphClass(),
                            ])
                                ->orWhereNull(['pageable_type', 'pageable_id']),
                        ),
                        fn (EloquentBuilder $query): EloquentBuilder => $query->whereNull(['pageable_id', 'pageable_type']),
                    )
                        ->ordered()
                        ->with(
                            'asset',
                            fn (BuilderContract $query): BuilderContract => $query->morphWith($this->getAssetRelations()),
                        ),
                ]),
            )
            ->get()
            ->keyBy('key')
            ->all();

        if ($withAssets) {
            foreach ($allWidgetAssets as $widgetAssets) {
                $hasPageAssets = $widgetAssets->assets->whereNotNull(['pageable_type', 'pageable_id'])->isNotEmpty();

                if ($hasPageAssets) {
                    $widgetAssets->setRelation('assets', $widgetAssets->assets->filter(
                        fn (WidgetAsset $asset): bool => $asset->pageable_type !== null && $asset->pageable_id !== null,
                    ));
                }
            }
        }

        return $allWidgetAssets;
    }
}
