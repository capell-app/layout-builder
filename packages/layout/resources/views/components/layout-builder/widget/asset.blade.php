<?php

declare(strict_types=1);

?>

@use(Capell\Layout\Livewire\LayoutBuilder;use Capell\Core\Models;use Capell\Admin\Facades\CapellAdmin;use Filament\Actions\Action;use Filament\Support\Enums\ActionSize;use Filament\Support\Enums\IconPosition;use Filament\Support\Enums\FontWeight)

@props([
    'containerKey',
    'description' => null,
    'index',
    'image' => null,
    'meta' => [],
    'name' => null,
    'occurrence',
    'pageId' => $this->page_id,
    'asset',
    'assetKey',
    'assetType',
    'widget',
    'widgetIndex',
])
@php
    /** @var Action $editWidgetAssetAction */
    $editWidgetAssetAction = ($this->editWidgetAssetAction)([
        'containerKey' => $containerKey,
        'widgetIndex' => $widgetIndex,
        'index' => $index,
        'assetType' => $assetType,
    ]);

    if (! $asset) {
        throw new Exception(
            "Widget '{$widget->key}({$occurrence})' asset not found: {$assetType} {$assetKey}",
        );
    }

    if (! $image) {
        $image = match (get_class($asset)) {
            Models\Page::class, Models\Content::class => $asset->image,
            Models\Media::class => $asset,
            default => null,
        };
    }

    $mediaCount = match (get_class($asset)) {
        Models\Content::class => $asset->media->count(),
        default => null,
    };

    $relatedCount = match (get_class($asset)) {
        Models\Content::class => $asset->related->count(),
        default => null,
    };

    $actionsCount = match (get_class($asset)) {
        Models\Content::class => count($asset->actions),
        default => null,
    };

    if (! $name) {
        $name = match (get_class($asset)) {
            Models\Page::class, Models\Content::class => $asset->name,
            Models\Media::class => $asset->title,
            default => null,
        };
    }

    if (! $description) {
        $description = match (get_class($asset)) {
            Models\Page::class, Models\Content::class => $asset->translation?->title &&
            $asset->translation->title !== $asset->name
                ? $asset->translation->title
                : null,
            default => null,
        };
    }
@endphp

<div
    x-sort:item="{{ $index }}"
    wire:key="{{ $assetKey }}"
    {{ $attributes->class(['flex gap-4 pr-4']) }}
>
    <div
        wire:click="{{ $editWidgetAssetAction->getLivewireClickHandler() }}"
        class="flex flex-1 flex-grow items-center"
    >
        <label
            x-on:click.stop
            class="group/asset flex h-full w-12 cursor-pointer items-center justify-center pl-2"
        >
            <x-capell-admin::forms.checkbox
                class="group-hover/asset:border-primary-500 group/asset-focus:border-primary-500 h-4 w-4 cursor-pointer border-gray-600"
                :label="__('tables::table.fields.bulk_select_asset.label', ['key' => $name])"
                :value="$assetKey"
                :wire:key="'selectedRecords'.$containerKey.'-'.$widgetIndex.'-'.$assetKey"
                x-model="selectedRecords['{{ $containerKey }}'][{{ $widgetIndex }}]"
                :x-show="'! isWidgetReorderingResources(\''.$containerKey.'\', '.$widgetIndex.')'"
                wire:loading.remove
                wire:target="{{ $editWidgetAssetAction->getLivewireClickHandler() }}"
            />

            <x-filament::loading-indicator
                class="text-primary-500 h-5 w-5"
                wire:loading
                :wire:target="$editWidgetAssetAction->getLivewireClickHandler()"
                :wire:loading.delay="config('filament.livewire_loading_delay', 'default')"
            />

            <div
                class="cursor-pointer transition"
                wire:loading.class="pointer-events-none opacity-40"
                x-sort:handle
                x-show="isWidgetReorderingResources('{{ $containerKey }}', {{ $widgetIndex }})"
                x-cloak
            >
                <x-filament::button
                    class="widget-asset-handle pointer-events-auto"
                    icon="heroicon-o-arrows-up-down"
                    color="primary"
                    size="sm"
                    label-sr-only
                    outlined
                />
            </div>
        </label>

        <div
            @class([
                'group/asset flex w-full cursor-pointer items-center gap-x-4',
                'lg:!grid lg:grid-cols-4 lg:gap-4' => $image,
            ])
        >
            <div @class(['py-3', 'lg:col-span-3' => $image])>
                <div
                    class="group-hover/asset:text-primary-600 dark:group-hover/asset:text-primary-400 line-clamp-1 text-sm font-medium text-gray-800 dark:text-gray-100"
                >
                    {{ $name }}
                    @svg($editWidgetAssetAction->getIcon(),
                        'group-hover/asset:text-primary-500 dark:group-hover/asset:text-primary-400 ml-1.5 inline h-5 w-5 text-gray-400 dark:text-gray-500',
                        ['x-tooltip.raw' => $editWidgetAssetAction->getTooltip()])
                </div>

                @if (! empty($description) && ! $description !== $name)
                    <div
                        class="line-clamp-2 text-xs leading-tight text-gray-600 dark:text-gray-300"
                    >
                        {{ $description }}
                    </div>
                @endif
            </div>

            <div
                class="flex shrink-0 grow items-center justify-end gap-x-6 gap-y-2 py-1.5"
            >
                @if ($mediaCount || $image)
                    <span class="relative">
                        @if ($image)
                            <x-curator-glider
                                class="max-h-12 object-contain"
                                format="webp"
                                view="capell-admin::components.media.glider"
                                :media="$image"
                                :width="80"
                                :height="80"
                                fit="fit"
                                loading="lazy"
                            />
                        @endif

                        @if ($mediaCount)
                            <span
                                class="pointer-events-none absolute -right-2 -top-2"
                            >
                                <x-filament::badge size="sm">
                                    {{ $mediaCount }}
                                </x-filament::badge>
                            </span>
                        @endif
                    </span>
                @endif

                @if ($actionsCount)
                    <span class="relative inline-block">
                        <x-filament::icon
                            icon="heroicon-c-arrow-path-rounded-square"
                            class="h-5 w-5"
                            color="gray"
                            :badge="$actionsCount"
                            :x-tooltip.raw="__('capell-admin::generic.actions')"
                        />
                        <span
                            class="pointer-events-none absolute -right-2 -top-2"
                        >
                            <x-filament::badge size="xs">
                                {{ $actionsCount }}
                            </x-filament::badge>
                        </span>
                    </span>
                @endif

                @if ($relatedCount)
                    <span class="relative inline-block">
                        <x-filament::icon
                            icon="heroicon-c-link"
                            class="h-5 w-5"
                            color="gray"
                            :x-tooltip.raw="__('capell-admin::generic.related')"
                        />
                        <span
                            class="pointer-events-none absolute -right-2 -top-2"
                        >
                            <x-filament::badge size="xs">
                                {{ $relatedCount }}
                            </x-filament::badge>
                        </span>
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="flex grow-0 flex-wrap items-center gap-x-3">
        <x-filament::dropdown
            class="fi-btn-group-dropdown"
            placement="bottom-end"
            teleport
        >
            <x-slot name="trigger">
                <x-filament::icon-button
                    icon="heroicon-o-ellipsis-vertical"
                    size="sm"
                    color="gray"
                />
            </x-slot>

            <x-filament::dropdown.list>
                <x-filament::dropdown.list.item
                    icon="heroicon-o-arrow-top-right-on-square"
                    target="_blank"
                    tag="a"
                    :href="CapellAdmin::getResourceUrl($assetType, $asset->getKey())"
                >
                    {{ __('capell-admin::button.edit_resource', ['type' => $assetType]) }}
                </x-filament::dropdown.list.item>
            </x-filament::dropdown.list>
        </x-filament::dropdown>
    </div>
</div>

<?php
