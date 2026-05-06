<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament\Concerns;

use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Configurators\Layouts\DefaultLayoutContainerConfigurator;
use Closure;
use Filament\FormBuilder\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection as SupportCollection;

trait ManagesContainers
{
    public function addContainer(string $key, ?int $position = null): void
    {
        $this->assertCanUpdateLayout();

        $container = [
            'widgets' => [],
        ];

        if ($position === null) {
            $this->containers[$key] = $container;
            $this->containerWidgets[$key] = [];
            $this->assets[$key] = [];

            return;
        }

        $position = min(count($this->containers), max(0, $position));

        $this->containers = array_slice($this->containers, 0, $position, true) +
            [$key => $container] +
            array_slice($this->containers, $position, null, true);

        $this->containerWidgets = array_slice($this->containerWidgets, 0, $position, true) +
            [$key => []] +
            array_slice($this->containerWidgets, $position, null, true);

        $this->assets = array_slice($this->assets, 0, $position, true) +
            [$key => []] +
            array_slice($this->assets, $position, null, true);
    }

    public function reorderContainers(string $containerKey, int $position): void
    {
        $this->assertCanUpdateLayout();

        $containers = $this->containers;

        $container = $containers[$containerKey];

        unset($containers[$containerKey]);

        $containers = array_slice($containers, 0, $position, true) +
            [$containerKey => $container] +
            array_slice($containers, $position, null, true);

        $this->containers = $containers;

        $this->layoutUpdated();
    }

    public function insertContainerAtPosition(int $position): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        $containerKey = $this->uniqueContainerKey();

        $this->addContainer($containerKey, $position);

        $this->layoutUpdated();
    }

    public function resizeContainer(string $containerKey, int $colspan): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        if (! isset($this->containers[$containerKey])) {
            return;
        }

        $this->containers[$containerKey]['meta'] ??= [];
        $this->containers[$containerKey]['meta']['colspan'] = min(12, max(1, $colspan));

        $this->layoutUpdated();
    }

    protected function duplicateContainer(string $containerKey): void
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

        $this->containerWidgets = array_slice($this->containerWidgets, 0, $insertPosition, true) +
            [$newContainerKey => $this->containerWidgets[$containerKey] ?? []] +
            array_slice($this->containerWidgets, $insertPosition, null, true);

        $this->assets = array_slice($this->assets, 0, $insertPosition, true) +
            [$newContainerKey => $this->assets[$containerKey] ?? []] +
            array_slice($this->assets, $insertPosition, null, true);

        $this->selectedRecords[$newContainerKey] = [];

        foreach (array_keys($this->containers[$newContainerKey]['widgets']) as $widgetIndex) {
            foreach ($this->assets[$newContainerKey][$widgetIndex] ?? [] as $assetIndex => $asset) {
                if (isset($asset['container'])) {
                    $this->assets[$newContainerKey][$widgetIndex][$assetIndex]['container'] = $newContainerKey;
                }
            }
        }

        $this->setupSelectedAssets();

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

        $this->containers[$key]['meta'] = $data['meta'] ?? [];

        $this->setupSelectedAssets();

        $this->layoutUpdated();
    }

    protected function removeContainer(string $containerKey): void
    {
        $this->assertCanUpdateLayout();

        foreach (['containers', 'containerWidgets', 'assets'] as $property) {
            if (! isset($this->{$property}[$containerKey])) {
                continue;
            }

            unset($this->{$property}[$containerKey]);
        }

        $this->layoutUpdated();
    }

    protected function updateContainerKey(string $oldKey, string $newKey): string
    {
        foreach (['containers', 'containerWidgets', 'assets'] as $property) {
            if (! isset($this->{$property}[$oldKey])) {
                continue;
            }

            $this->{$property}[$newKey] = $this->{$property}[$oldKey];

            unset($this->{$property}[$oldKey]);
        }

        foreach ($this->containers[$newKey]['widgets'] as $widgetIndex => $widget) {
            $widget['old_container'] ??= $oldKey;
            $widget['container_key'] = $newKey;

            $this->containers[$newKey]['widgets'][$widgetIndex] = $widget;
        }

        foreach ($this->assets[$newKey] ?? [] as $widgetIndex => $widgetAssets) {
            foreach ($widgetAssets as $assetIndex => $asset) {
                $asset['old_container'] ??= $oldKey;
                $asset['container'] = $newKey;

                $this->assets[$newKey][$widgetIndex][$assetIndex] = $asset;
            }
        }

        $originalContainerWidgetAssets = $this->originalAssets[$oldKey] ?? [];
        unset($this->originalAssets[$oldKey]);
        $this->originalAssets[$newKey] = $originalContainerWidgetAssets;

        if (isset($this->selectedRecords[$oldKey])) {
            $this->selectedRecords[$newKey] = $this->selectedRecords[$oldKey];

            unset($this->selectedRecords[$oldKey]);
        }

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

        if (! $this->layout->containers) {
            return;
        }

        foreach ($this->layout->containers as $key => $container) {
            $this->containers[$key] = [
                'widgets' => $container['widgets'] ?? [],
                'meta' => $container['meta'] ?? [],
            ];
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
                ->helperText(__('Lowercase text, numbers, hyphens, and underscores only'))
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
            ...$typeSchema,
        ];
    }

    protected function getContainerOptions(): SupportCollection
    {
        return collect($this->containers)
            ->keys()
            ->mapWithKeys(fn (string $container): array => [$container => __($container)]);
    }
}
