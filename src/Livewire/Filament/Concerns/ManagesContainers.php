<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament\Concerns;

use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\LayoutBuilder\Actions\Mutations\ReorderLayoutContainerAction;
use Capell\LayoutBuilder\Actions\Mutations\ResizeLayoutContainerAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
use Capell\LayoutBuilder\Filament\Configurators\Layouts\DefaultLayoutContainerConfigurator;
use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection as SupportCollection;

trait ManagesContainers
{
    public function addContainer(string $key, ?int $position = null): void
    {
        $this->assertCanUpdateLayout();

        $container = [
            'elements' => [],
        ];

        if ($position === null) {
            $this->containers[$key] = $container;
            $this->containerElements[$key] = [];
            $this->assets[$key] = [];
            $this->trackKnownContainerKey($key);

            return;
        }

        $position = min(count($this->containers), max(0, $position));

        $this->containers = array_slice($this->containers, 0, $position, true) +
            [$key => $container] +
            array_slice($this->containers, $position, null, true);

        $this->containerElements = array_slice($this->containerElements, 0, $position, true) +
            [$key => []] +
            array_slice($this->containerElements, $position, null, true);

        $this->assets = array_slice($this->assets, 0, $position, true) +
            [$key => []] +
            array_slice($this->assets, $position, null, true);

        $this->trackKnownContainerKey($key);
    }

    public function reorderContainers(string $containerKey, int $position): void
    {
        $this->assertCanUpdateLayout();

        $result = ReorderLayoutContainerAction::run(
            state: LayoutBuilderStateData::fromLivewire($this->containers, $this->assets, $this->originalAssets, $this->selectedRecords),
            containerKey: $containerKey,
            position: $position,
        );

        $this->applyLayoutMutationResult($result);
    }

    public function moveContainerUp(string $containerKey): void
    {
        $this->moveContainer($containerKey, -1);
    }

    public function moveContainerDown(string $containerKey): void
    {
        $this->moveContainer($containerKey, 1);
    }

    public function insertContainerAtPosition(int $position): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        $containerKey = $this->uniqueContainerKey();

        $this->addContainer($containerKey, $position);

        $this->layoutUpdated();
    }

    public function resizeContainer(string $containerKey, int $colspan, ?string $breakpoint = null): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        $result = ResizeLayoutContainerAction::run(
            state: LayoutBuilderStateData::fromLivewire($this->containers, $this->assets, $this->originalAssets, $this->selectedRecords),
            containerKey: $containerKey,
            colspan: $colspan,
            breakpoint: $this->currentLayoutBreakpoint($breakpoint),
        );

        $this->applyLayoutMutationResult($result);
    }

    public function duplicateContainer(string $containerKey): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        if (! isset($this->containers[$containerKey])) {
            return;
        }

        $newContainerKey = $this->uniqueContainerKey();
        $containerPosition = array_search($containerKey, array_keys($this->containers), true);

        if ($containerPosition === false) {
            return;
        }

        $insertPosition = $containerPosition + 1;

        $this->containers = array_slice($this->containers, 0, $insertPosition, true) +
            [$newContainerKey => $this->containers[$containerKey]] +
            array_slice($this->containers, $insertPosition, null, true);

        $this->containerElements = array_slice($this->containerElements, 0, $insertPosition, true) +
            [$newContainerKey => $this->containerElements[$containerKey] ?? []] +
            array_slice($this->containerElements, $insertPosition, null, true);

        $this->assets = array_slice($this->assets, 0, $insertPosition, true) +
            [$newContainerKey => $this->assets[$containerKey] ?? []] +
            array_slice($this->assets, $insertPosition, null, true);

        $this->selectedRecords[$newContainerKey] = [];

        foreach (array_keys($this->containers[$newContainerKey]['elements']) as $elementIndex) {
            foreach ($this->assets[$newContainerKey][$elementIndex] ?? [] as $assetIndex => $asset) {
                if (isset($asset['container'])) {
                    $this->assets[$newContainerKey][$elementIndex][$assetIndex]['container'] = $newContainerKey;
                }
            }
        }

        $this->setupSelectedAssets();

        $this->trackKnownContainerKey($newContainerKey);
        $this->layoutUpdated();
    }

    protected function saveContainer(array $data, ?string $key = null, ?int $position = null): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        if (in_array($key, [null, '', '0'], true)) {
            $key = $data['key'];
        }

        if ($key !== $data['key']) {
            $key = $this->updateContainerKey($key, $data['key']);
        }

        if (! isset($this->containers[$key])) {
            $this->addContainer($key, $position);
        }

        $meta = $data['meta'] ?? [];
        $meta['area'] ??= LayoutAreaRegistry::MAIN;

        $this->containers[$key]['meta'] = $meta;

        $this->setupSelectedAssets();

        $this->layoutUpdated();
    }

    protected function removeContainer(string $containerKey): void
    {
        $this->assertCanUpdateLayout();

        foreach (['containers', 'containerElements', 'assets'] as $property) {
            if (! isset($this->{$property}[$containerKey])) {
                continue;
            }

            unset($this->{$property}[$containerKey]);
        }

        $this->forgetKnownContainerKey($containerKey);

        $this->layoutUpdated();
    }

    protected function canMoveContainerUp(string $containerKey): bool
    {
        return $this->containerPosition($containerKey) > 0;
    }

    protected function canMoveContainerDown(string $containerKey): bool
    {
        $position = $this->containerPosition($containerKey);

        return $position !== null && $position < count($this->containers) - 1;
    }

    protected function updateContainerKey(string $oldKey, string $newKey): string
    {
        foreach (['containers', 'containerElements', 'assets'] as $property) {
            if (! isset($this->{$property}[$oldKey])) {
                continue;
            }

            $this->{$property}[$newKey] = $this->{$property}[$oldKey];

            unset($this->{$property}[$oldKey]);
        }

        foreach ($this->containers[$newKey]['elements'] as $elementIndex => $element) {
            $element['old_container'] ??= $oldKey;
            $element['container_key'] = $newKey;

            $this->containers[$newKey]['elements'][$elementIndex] = $element;
        }

        foreach ($this->assets[$newKey] ?? [] as $elementIndex => $elementAssets) {
            foreach ($elementAssets as $assetIndex => $asset) {
                $asset['old_container'] ??= $oldKey;
                $asset['container'] = $newKey;

                $this->assets[$newKey][$elementIndex][$assetIndex] = $asset;
            }
        }

        $originalContainerElementAssets = $this->originalAssets[$oldKey] ?? [];
        unset($this->originalAssets[$oldKey]);
        $this->originalAssets[$newKey] = $originalContainerElementAssets;

        if (isset($this->selectedRecords[$oldKey])) {
            $this->selectedRecords[$newKey] = $this->selectedRecords[$oldKey];

            unset($this->selectedRecords[$oldKey]);
        }

        $this->forgetKnownContainerKey($oldKey);
        $this->trackKnownContainerKey($newKey);

        return $newKey;
    }

    protected function uniqueContainerKey(): string
    {
        $index = count($this->containers) + 1;

        do {
            $key = 'container-' . $index;
            $index++;
        } while (isset($this->containers[$key]));

        return $key;
    }

    protected function setupContainers(): void
    {
        if ($this->containers !== null) {
            return;
        }

        $this->containers = [];

        $containers = $this->layout->getAttribute('containers');

        if (! is_array($containers) || $containers === []) {
            return;
        }

        foreach ($containers as $key => $container) {
            $this->containers[$key] = [
                'elements' => $container['elements'] ?? [],
                'meta' => $container['meta'] ?? [],
            ];
            $this->trackKnownContainerKey((string) $key);
        }
    }

    protected function getContainerSchema(Schema $configurator, array $arguments): array
    {
        $containerKey = $arguments['containerKey'] ?? null;

        $adminSchema = AdminSurfaceLookup::configurator(
            ConfiguratorTypeEnum::LayoutContainer->value,
            $this->layout->admin['container_schema'][$containerKey] ?? DefaultLayoutContainerConfigurator::getKey(),
        );

        $typeSchema = resolve($adminSchema)->make($configurator);

        return [
            TextInput::make('key')
                ->label(__('capell-admin::form.key'))
                ->placeholder(__('capell-admin::generic.key_placeholder'))
                ->helperText(__('capell-layout-builder::message.container_key_helper'))
                ->alphaDash()
                ->required()
                ->maxLength(128)
                ->afterStateHydrated(
                    fn (TextInput $component, ?string $state): TextInput => $component->state(
                        str($state)->slug()->lower()->toString(),
                    ),
                )
                ->dehydrateStateUsing(fn (?string $state): string => str($state)->slug()->lower()->toString())
                ->rules([
                    fn (self $livewire): Closure => function (string $attribute, string $value, Closure $fail) use ($livewire, $containerKey): void {
                        if (! isset($livewire->containers[$value]) || ($containerKey && $containerKey === $value)) {
                            return;
                        }

                        $fail(__('capell-layout-builder::message.layout_container_key_not_unique', ['key' => $value]));
                    },
                ]),
            Select::make('meta.area')
                ->label(__('capell-layout-builder::form.area'))
                ->options(fn (): array => $this->layoutAreaOptions())
                ->default(LayoutAreaRegistry::MAIN)
                ->required()
                ->native(false),
            ...$typeSchema,
        ];
    }

    protected function getContainerOptions(): SupportCollection
    {
        return collect($this->containers)
            ->keys()
            ->mapWithKeys(fn (string $container): array => [$container => __($container)]);
    }

    private function moveContainer(string $containerKey, int $direction): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        $currentPosition = $this->containerPosition($containerKey);

        if ($currentPosition === null) {
            return;
        }

        $targetPosition = $currentPosition + $direction;

        if ($targetPosition < 0 || $targetPosition >= count($this->containers)) {
            return;
        }

        $this->reorderContainers($containerKey, $targetPosition);
    }

    private function containerPosition(string $containerKey): ?int
    {
        $position = array_search($containerKey, array_keys($this->containers), true);

        return $position === false ? null : $position;
    }

    private function currentLayoutBreakpoint(?string $breakpoint = null): ?LayoutBreakpoint
    {
        if ($breakpoint !== null) {
            return LayoutBreakpoint::fromNullable($breakpoint);
        }

        if (! property_exists($this, 'activeBreakpoint')) {
            return null;
        }

        $breakpoint = $this->activeBreakpoint;

        if ($breakpoint instanceof LayoutBreakpoint) {
            return $breakpoint;
        }

        if (is_string($breakpoint)) {
            return LayoutBreakpoint::fromNullable($breakpoint);
        }

        return null;
    }

    private function trackKnownContainerKey(string $containerKey): void
    {
        if (in_array($containerKey, $this->knownContainerKeys, true)) {
            return;
        }

        $this->knownContainerKeys[] = $containerKey;
    }

    private function forgetKnownContainerKey(string $containerKey): void
    {
        $this->knownContainerKeys = array_values(array_filter(
            $this->knownContainerKeys,
            fn (string $knownContainerKey): bool => $knownContainerKey !== $containerKey,
        ));
    }
}
