<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Filament\Concerns;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Layout\Enums\TypeSchemaEnum;
use Capell\Layout\Filament\Resources\Layouts\Schemas\Types\Containers\DefaultLayoutContainerSchema;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection as SupportCollection;

trait ManagesContainers
{
    public function addContainer(string $key): void
    {
        $this->containers[$key] = [
            'widgets' => [],
        ];

        $this->containerWidgets[$key] = [];

        $this->assets[$key] = [];
    }

    public function reorderContainers(string $containerKey, int $position): void
    {
        $containers = $this->containers;

        $container = $containers[$containerKey];

        unset($containers[$containerKey]);

        $containers = array_slice($containers, 0, $position, true) +
            [$containerKey => $container] +
            array_slice($containers, $position, null, true);

        $this->containers = $containers;

        $this->layoutUpdated();
    }

    protected function saveContainer(array $data, ?string $key = null): void
    {
        $this->ensureLoaded();

        if (in_array($key, [null, '', '0'], true)) {
            $key = $data['key'];
        }

        if ($key !== $data['key']) {
            $key = $this->updateContainerKey($key, $data['key']);
        }

        if (! isset($this->containers[$key])) {
            $this->addContainer($key);
        }

        $this->containers[$key]['meta'] = $data['meta'] ?? [];

        $this->setupSelectedAssets();

        $this->layoutUpdated();
    }

    protected function removeContainer(string $containerKey): void
    {
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

    protected function getContainerSchema(Schema $schema, array $arguments): array
    {
        $containerKey = $arguments['containerKey'] ?? null;

        $adminSchema = CapellAdmin::getSchema(
            TypeSchemaEnum::LayoutContainer->value,
            $this->layout->admin['container_schema'][$containerKey] ?? DefaultLayoutContainerSchema::getKey(),
        );

        $typeSchema = resolve($adminSchema)->make($schema);

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

                        $fail(__('capell-layout::message.layout_container_key_not_unique', ['key' => $value]));
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
