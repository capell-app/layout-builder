<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament\Concerns;

use Capell\LayoutBuilder\Actions\Mutations\ReorderLayoutElementAction;
use Capell\LayoutBuilder\Actions\ResolveAdminElementPreviewDataAction;
use Capell\LayoutBuilder\Data\AdminElementPreviewData;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Models\ElementAsset;
use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;

trait ManagesElements
{
    public function addElementToContainer(Element $element, string $containerKey): int
    {
        $this->assertCanUpdateLayout();

        $occurrence = $this->getLastContainerElementOccurrence($containerKey, $element->key) + 1;

        $this->containers[$containerKey]['elements'][] = [
            'element_key' => $element->key,
            'occurrence' => $occurrence,
        ];

        $index = array_key_last($this->containers[$containerKey]['elements']);

        $this->containerElements[$containerKey][$index] = $element;

        $this->assets[$containerKey][$index] = [];

        return $index;
    }

    public function addElementToContainerAtPosition(Element $element, string $containerKey, ?int $position = null): int
    {
        $elementIndex = $this->addElementToContainer($element, $containerKey);

        if ($position === null || $position >= $elementIndex) {
            return $elementIndex;
        }

        $position = max(0, $position);

        $this->insertContainerElementAtPosition($containerKey, $elementIndex, $position);

        return $position;
    }

    public function reorderElements(string $containerKey, string $containerElementIndex, int $elementIndex): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        [$originalContainer, $originalIndex] = explode('.', $containerElementIndex);

        $originalIndex = (int) $originalIndex;

        $this->moveLoadedContainerElement($originalContainer, $originalIndex, $containerKey, $elementIndex);

        $result = ReorderLayoutElementAction::run(
            state: LayoutBuilderStateData::fromLivewire($this->containers, $this->assets, $this->originalAssets, $this->selectedRecords),
            originalContainer: $originalContainer,
            targetContainer: $containerKey,
            originalIndex: $originalIndex,
            targetIndex: $elementIndex,
        );

        $this->applyLayoutMutationResult($result);

        if (isset($this->containers[$containerKey]['elements'][$elementIndex])) {
            $this->updatePageAssets($containerKey, $elementIndex);
        }
    }

    public function moveElementUp(string $containerKey, int $elementIndex): void
    {
        if (! $this->canMoveElementUp($containerKey, $elementIndex)) {
            return;
        }

        $this->reorderElements($containerKey, $containerKey . '.' . $elementIndex, $elementIndex - 1);
    }

    public function moveElementDown(string $containerKey, int $elementIndex): void
    {
        if (! $this->canMoveElementDown($containerKey, $elementIndex)) {
            return;
        }

        $this->reorderElements($containerKey, $containerKey . '.' . $elementIndex, $elementIndex + 1);
    }

    public function moveElementToContainer(string $containerKey, int $elementIndex, string $targetContainerKey): void
    {
        if (! $this->canMoveElementToContainer($containerKey, $elementIndex, $targetContainerKey)) {
            return;
        }

        $targetIndex = count($this->containers[$targetContainerKey]['elements']);

        $this->reorderElements($targetContainerKey, $containerKey . '.' . $elementIndex, $targetIndex);
    }

    public function canMoveElementUp(string $containerKey, int $elementIndex): bool
    {
        $this->ensureLoaded();

        return isset($this->containers[$containerKey]['elements'][$elementIndex])
            && $elementIndex > 0;
    }

    public function canMoveElementDown(string $containerKey, int $elementIndex): bool
    {
        $this->ensureLoaded();

        return isset($this->containers[$containerKey]['elements'][$elementIndex])
            && $elementIndex < count($this->containers[$containerKey]['elements']) - 1;
    }

    public function canMoveElementToContainer(string $containerKey, int $elementIndex, string $targetContainerKey): bool
    {
        $this->ensureLoaded();

        return isset($this->containers[$containerKey]['elements'][$elementIndex])
            && isset($this->containers[$targetContainerKey])
            && $containerKey !== $targetContainerKey;
    }

    public function canMoveElementToAnotherContainer(string $containerKey, int $elementIndex): bool
    {
        $this->ensureLoaded();

        return isset($this->containers[$containerKey]['elements'][$elementIndex])
            && collect($this->containers)
                ->keys()
                ->contains(fn (string $targetContainerKey): bool => $targetContainerKey !== $containerKey);
    }

    public function resolveAdminElementPreviewData(string $containerKey, int $elementIndex): AdminElementPreviewData
    {
        $element = $this->getContainerElement($containerKey, $elementIndex);

        return ResolveAdminElementPreviewDataAction::run(
            element: $element,
            containerElement: $this->containers[$containerKey]['elements'][$elementIndex],
            page: $this->page,
            assetCount: $this->countElementAssets($containerKey, $elementIndex),
            hasPageAssets: $this->hasPageAssets($containerKey, $elementIndex),
        );
    }

    public function resolveAdminElementPreviewView(AdminElementPreviewData $previewData): string
    {
        return $previewData->view;
    }

    public function duplicateElement(string $containerKey, int $originalIndex, bool $withAssets = true): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        $containerElement = $this->containers[$containerKey]['elements'][$originalIndex];

        $containerElement['occurrence'] = $this->getLastContainerElementOccurrence($containerKey, $containerElement['element_key']) + 1;

        $this->containers[$containerKey]['elements'][] = $containerElement;

        $this->containerElements[$containerKey][] = $this->getContainerElement($containerKey, $originalIndex);
        $elementIndex = array_key_last($this->containerElements[$containerKey]);

        if ($withAssets) {
            $this->assets[$containerKey][$elementIndex] = $this->assets[$containerKey][$originalIndex];
        }

        $this->layoutUpdated();
    }

    public function removeElement(string $containerKey, int $elementIndex): void
    {
        $this->assertCanUpdateLayout();

        if (isset($this->containers[$containerKey]['elements'][$elementIndex])) {
            unset($this->containers[$containerKey]['elements'][$elementIndex]);
            $this->containers[$containerKey]['elements'] = array_values($this->containers[$containerKey]['elements']);
        }

        if (isset($this->containerElements[$containerKey][$elementIndex])) {
            unset($this->containerElements[$containerKey][$elementIndex]);
            $this->containerElements[$containerKey] = array_values($this->containerElements[$containerKey]);
        }

        if (isset($this->assets[$containerKey][$elementIndex])) {
            unset($this->assets[$containerKey][$elementIndex]);
            $this->assets[$containerKey] = array_values($this->assets[$containerKey]);
        }

        if (isset($this->selectedRecords[$containerKey][$elementIndex])) {
            unset($this->selectedRecords[$containerKey][$elementIndex]);
            $this->selectedRecords[$containerKey] = array_values($this->selectedRecords[$containerKey]);
        }

        $this->layoutUpdated();
    }

    public function editLayoutElement(string $containerKey, int $elementIndex, array $data): void
    {
        $this->ensureLoaded();

        $this->containers[$containerKey]['elements'][$elementIndex]['meta'] = array_merge(
            $this->containers[$containerKey]['elements'][$elementIndex]['meta'] ?? [],
            $data,
        );

        $this->layoutUpdated();
    }

    public function getContainerElement(string $containerKey, int $elementIndex): Element
    {
        if (! isset($this->containerElements[$containerKey][$elementIndex])) {
            $this->ensureLoaded();
        }

        if (! isset($this->containerElements[$containerKey][$elementIndex])) {
            $element = $this->loadElement($containerKey, $elementIndex, withAssets: false);

            $assets = $this->loadElementAssetsFor($element, $containerKey, $elementIndex);

            $element->setRelation('assets', $assets);
        }

        return $this->containerElements[$containerKey][$elementIndex];
    }

    public function getContainerElementConfigurator(string $containerKey, int $elementIndex): ?string
    {
        return $this->getContainerElement($containerKey, $elementIndex)?->type->admin['layout_element_configurator']
            ?? null;
    }

    public function getContainerElementOccurrence(string $containerKey, int $elementIndex): int
    {
        return (int) ($this->containers[$containerKey]['elements'][$elementIndex]['occurrence'] ?? 1);
    }

    protected function moveContainerElement(string $originalContainer, int $originalIndex, string $containerKey, int $elementIndex): void
    {
        $element = $this->getContainerElement($originalContainer, $originalIndex);

        $containerElement = $this->containers[$originalContainer]['elements'][$originalIndex];

        if ($originalContainer !== $containerKey) {
            $containerElement['occurrence'] = $this->getLastContainerElementOccurrence(
                containerKey: $containerKey,
                elementKey: $containerElement['element_key'],
                elements: $this->containers[$containerKey]['elements'],
            ) + 1;
        }

        $elements = $this->containers[$originalContainer]['elements'];

        unset($elements[$originalIndex]);

        $this->containers[$originalContainer]['elements'] = array_values($elements);

        $elements = $this->containers[$containerKey]['elements'];
        $elements = array_merge(array_slice($elements, 0, $elementIndex), [$containerElement], array_slice($elements, $elementIndex));
        $this->containers[$containerKey]['elements'] = $elements;

        if ($containerKey !== $originalContainer) {
            unset($this->containerElements[$originalContainer][$originalIndex]);
            $this->containerElements[$originalContainer] = array_values($this->containerElements[$originalContainer]);
        }

        $containerElements = $this->containerElements[$containerKey] ?? [];
        $containerElements = array_merge(array_slice($containerElements, 0, $elementIndex), [$element], array_slice($containerElements, $elementIndex));
        $this->containerElements[$containerKey] = $containerElements;

        $this->originalAssets ??= [];

        $originalContainerElementAssets = $this->originalAssets[$originalContainer][$originalIndex] ?? [];

        if ($containerKey !== $originalContainer && isset($this->originalAssets[$originalContainer][$originalIndex])) {
            unset($this->originalAssets[$originalContainer][$originalIndex]);
            $this->originalAssets[$originalContainer] = array_values($this->originalAssets[$originalContainer]);
        }

        $targetOriginalAssets = $this->originalAssets[$containerKey] ?? [];
        $targetOriginalAssets = array_merge(
            array_slice($targetOriginalAssets, 0, $elementIndex),
            [$originalContainerElementAssets],
            array_slice($targetOriginalAssets, $elementIndex),
        );
        $this->originalAssets[$containerKey] = $targetOriginalAssets;

        $this->updatePageAssets($containerKey, $elementIndex);
    }

    protected function insertContainerElementAtPosition(string $containerKey, int $originalIndex, int $position): void
    {
        if (isset($this->containers[$containerKey]['elements'][$originalIndex])) {
            $this->containers[$containerKey]['elements'] = $this->insertArrayItemAtPosition(
                $this->containers[$containerKey]['elements'],
                $originalIndex,
                $position,
            );
        }

        foreach (['containerElements', 'assets', 'originalAssets', 'selectedRecords'] as $property) {
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

    protected function moveLoadedContainerElement(string $originalContainer, int $originalIndex, string $containerKey, int $elementIndex): void
    {
        if (! isset($this->containerElements[$originalContainer][$originalIndex])) {
            return;
        }

        $element = $this->containerElements[$originalContainer][$originalIndex];

        unset($this->containerElements[$originalContainer][$originalIndex]);
        $this->containerElements[$originalContainer] = array_values($this->containerElements[$originalContainer]);

        $containerElements = $this->containerElements[$containerKey] ?? [];
        $elementIndex = min(count($containerElements), max(0, $elementIndex));

        $this->containerElements[$containerKey] = array_merge(
            array_slice($containerElements, 0, $elementIndex),
            [$element],
            array_slice($containerElements, $elementIndex),
        );
    }

    protected function normalizeContainerElementOccurrences(string $containerKey): void
    {
        if (! isset($this->containers[$containerKey]['elements'])) {
            return;
        }

        $occurrences = [];

        foreach ($this->containers[$containerKey]['elements'] as $elementIndex => $containerElement) {
            $elementKey = $containerElement['element_key'];
            $occurrences[$elementKey] = ($occurrences[$elementKey] ?? 0) + 1;
            $occurrence = $occurrences[$elementKey];

            $this->containers[$containerKey]['elements'][$elementIndex]['occurrence'] = $occurrence;

            foreach (['assets', 'originalAssets'] as $property) {
                foreach (array_keys($this->{$property}[$containerKey][$elementIndex] ?? []) as $assetIndex) {
                    $this->{$property}[$containerKey][$elementIndex][$assetIndex]['occurrence'] = $occurrence;
                }
            }
        }
    }

    protected function getContainerElementKeys(): array
    {
        return collect($this->containers)
            ->pluck('elements.*.element_key')
            ->flatten()
            ->unique()
            ->toArray();
    }

    protected function getLastContainerElementOccurrence(string $containerKey, string $elementKey, ?int $compareIndex = null, ?array $elements = null): int
    {
        if ($elements === null || $elements === []) {
            $elements = $this->containers[$containerKey]['elements'];
        }

        $occurrence = 1;

        foreach ($elements as $elementIndex => $element) {
            if ($compareIndex !== null && $elementIndex === $compareIndex) {
                return $occurrence;
            }

            if ($element['element_key'] === $elementKey) {
                $occurrence++;
            }
        }

        return $occurrence;
    }

    protected function loadElement(string $containerKey, int $elementIndex, bool $withAssets = true): Element
    {
        $container = $this->containers[$containerKey] ?? null;

        throw_if($container === null || ! isset($container['elements'][$elementIndex]), Exception::class, 'Container element not found for container: ' . $containerKey . ' index: ' . $elementIndex);

        $containerElement = $container['elements'][$elementIndex];
        $elementKey = $containerElement['element_key'];
        $occurrence = $containerElement['occurrence'] ?? 1;

        $element = $this->getElement($elementKey);

        if ($withAssets) {
            $element->setRelation('assets', $this->loadElementAssets($element, $containerKey, $occurrence));
        }

        $this->containerElements[$containerKey][$elementIndex] = $element;

        return $element;
    }

    protected function setupContainerElements(string $containerKey, array $allElements, ?array $allElementAssets = null): void
    {
        $container = $this->containers[$containerKey];

        $elementOccurrences = [];

        foreach ($container['elements'] as $elementIndex => $containerElement) {
            $elementKey = $containerElement['element_key'];
            $oldContainerKey = $containerElement['old_container'] ?? null;

            throw_unless(isset($allElements[$elementKey]), Exception::class, 'Element not found for key: ' . $elementKey);

            /** @var Element $element */
            $element = clone $allElements[$elementKey];

            if (! isset($elementOccurrences[$elementKey])) {
                $elementOccurrences[$elementKey] = 1;
            } else {
                $elementOccurrences[$elementKey]++;
            }

            $elementOccurrence = $elementOccurrences[$elementKey];

            $this->containers[$containerKey]['elements'][$elementIndex]['occurrence'] = $elementOccurrence;

            if ($allElementAssets !== null) {
                $assets = $allElementAssets[$containerKey][$elementIndex] ?? new Collection;
            } elseif ($element->relationLoaded('assets')) {
                $assets = $element->assets;
            } else {
                $assets = $this->loadElementAssets($element, $containerKey, $elementOccurrence);
            }

            $element->setRelation(
                'assets',
                $this->filterContainerElementAssets($assets, $oldContainerKey ?? $containerKey, $elementOccurrence, $element),
            );

            $this->containerElements[$containerKey][$elementIndex] = $element;

            $this->assets[$containerKey][$elementIndex] = $this->mapElementAssets($element, $containerKey, $oldContainerKey);

            $this->updatePageAssets($containerKey, $elementIndex);
        }
    }

    /**
     * @throws Exception
     */
    protected function getElement(int|string $id, bool $withRelations = true): Element
    {
        $query = $this->getElementQuery(withRelations: $withRelations);

        if (is_numeric($id)) {
            $query->whereKey($id);
        } else {
            $query->where('key', $id);
        }

        /** @var Element|null $element */
        $element = $query->first();

        throw_unless($element, Exception::class, sprintf("Unable to find '%s' element", (string) $id));

        return $element;
    }

    /**
     * @return EloquentBuilder<Element>
     */
    protected function getElementQuery(bool $withRelations = true): EloquentBuilder
    {
        /** @var class-string<Element> $model */
        $model = Element::class;

        return $model::query()
            ->when(
                $withRelations,
                fn (EloquentBuilder $query) => $query->withCount([
                    'layouts',
                    'elementPageAssets as page_assets_count' => fn (EloquentBuilder $query): EloquentBuilder => $query->distinct(['pageable_id', 'pageable_type'])
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

    /**
     * @return EloquentBuilder<Element>
     */
    protected function getElementDisplayQuery(): EloquentBuilder
    {
        return $this->getElementQuery(withRelations: false)
            ->withCount([
                'layouts',
                'elementPageAssets as page_assets_count' => fn (EloquentBuilder $query): EloquentBuilder => $query->distinct(['pageable_id', 'pageable_type'])
                    ->when(
                        $this->inPageContext(),
                        fn (EloquentBuilder $query) => $query->where([
                            'pageable_type' => $this->page->getMorphClass(),
                            'pageable_id' => $this->page->getKey(),
                        ]),
                    ),
            ])
            ->with([
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
                    ->whereIn('workspace_id', $this->getCurrentContainerElementAssetWorkspaceIds())
                    ->ordered()
                    ->with(
                        'asset',
                        fn (MorphTo $query): MorphTo => $query->morphWith($this->getAssetRelations()),
                    ),
                'type',
                'translation' => fn (BuilderContract $query): BuilderContract => $query->orderBy('language_id'),
            ]);
    }

    protected function preloadAllElements(bool $withAssets = true): array
    {
        $elementKeys = $this->getContainerElementKeys();

        if ($elementKeys === []) {
            return [];
        }

        $allElementAssets = $this->getElementQuery()
            ->whereIn('key', $elementKeys)
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
                        ->whereIn('workspace_id', $this->getCurrentContainerElementAssetWorkspaceIds())
                        ->ordered()
                        ->with(
                            'asset',
                            fn (MorphTo $query): MorphTo => $query->morphWith($this->getAssetRelations()),
                        ),
                ]),
            )
            ->get()
            ->keyBy('key')
            ->all();

        if ($withAssets) {
            foreach ($allElementAssets as $elementAssets) {
                $hasPageAssets = $elementAssets->assets->whereNotNull(['pageable_type', 'pageable_id'])->isNotEmpty();

                if ($hasPageAssets) {
                    $elementAssets->setRelation('assets', $elementAssets->assets->filter(
                        fn (ElementAsset $asset): bool => $asset->pageable_type !== null && $asset->pageable_id !== null,
                    ));
                }
            }
        }

        return $allElementAssets;
    }
}
