@props([
    'containerKey',
    'containerWidget',
    'loop',
    'widget',
    'widgetIndex',
])
{{-- format-ignore-start --}}
@php
    use Capell\Core\Enums\MediaConversionEnum;
    use Capell\Core\Enums\MediaCollectionEnum;
    use Capell\Core\Facades\CapellCore;
    use Capell\Mosaic\Livewire\Filament\LayoutBuilder;
    use Filament\Support\Enums\FontWeight;
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Enums\Size;
    use Illuminate\View\ComponentAttributeBag;

    /**
     * @var \Capell\Mosaic\Models\Widget $widget
    */

    /**
     * @var LayoutBuilder $this
     */
    $occurrence = $containerWidget['occurrence'] ?? 1;

    $containerWidgetKey = "widget-{$containerKey}-{$widget->key}-{$occurrence}";

    $type = $widget->admin['type'] ?? ($widget->type->admin['type'] ?? []);

    $assetTypes = ! empty($widget->admin['asset_types'])
        ? $widget->admin['asset_types']
        : ($widget->type->admin['asset_types'] ?? []);

    $widgetIcon = $widget->admin['icon'] ?? ($widget->type->admin['icon'] ?? null);

    $hasPageAssets = $this->hasPageAssets($containerKey, $widgetIndex);

    $editWidgetAction = ($this->editWidgetAction)(['containerKey' => $containerKey, 'widgetIndex' => $widgetIndex]);

    $editLayoutWidgetAction = ($this->editLayoutWidgetAction)([
        'containerKey' => $containerKey,
        'widgetIndex' => $widgetIndex,
    ]);

    $togglePageAssetsAction = ($this->togglePageAssetsAction)([
        'containerKey' => $containerKey,
        'widgetIndex' => $widgetIndex,
    ]);

    $image = $widget->getMedia(MediaCollectionEnum::Image->value)->first() ?: $widget->getMedia(MediaCollectionEnum::BackgroundImage->value)->first();

    $title = $widget->translation?->title;
@endphp
{{-- format-ignore-end --}}
<div
    x-data="{
        isCollapsed: true,
        id: '{{ $widgetIndex }}',
        containerKey: '{{ $containerKey }}',
        notify() {
            this.$dispatch('widget-collapsed-changed', {
                id: this.id,
                containerKey: this.containerKey,
                isCollapsed: this.isCollapsed,
            })
        },
        toggleCollapse() {
            this.isCollapsed = ! this.isCollapsed
            this.notify()
        },
    }"
    {{
        $attributes->class(['layout-container-widget group last:rounded-b-lg'])->when(
            $assetTypes,
            fn (ComponentAttributeBag $attributeBag): ComponentAttributeBag => $attributeBag->merge([
                ':class' => "{ 'pb-4': ! isCollapsed }",
            ]),
        )
    }}
    wire:key="{{ "{$containerWidgetKey}" }}"
    x-sort:item="'{{ $containerKey . '.' . $widgetIndex }}'"
    x-init="
        $dispatch('widget-collapsed-register', {
            id: id,
            containerKey: containerKey,
            isCollapsed: isCollapsed,
        })
    "
    x-on:collapse-widget.window="
        if ($event.detail.containerKey && $event.detail.containerKey !== containerKey)
            return
        if ($event.detail.id && $event.detail.id !== id) return
        isCollapsed = $event.detail.isCollapsed
        notify()
    "
    x-on:refresh-assets.window="
        $event.detail.containerKey === '{{ $containerKey }}' &&
        $event.detail.widgetIndex === {{ $widgetIndex }} &&
        isCollapsed === true
            ? ((isCollapsed = false), notify())
            : null
    "
>
    <div class="flex min-h-[52px]">
        <div
            class="group/widget layout-builder-widget-heading !lg:px-4 flex flex-1 items-center gap-4 px-4 py-3 group-[&:last-child]:rounded-b-lg"
            :class="{
                '!rounded-b-none pb-2' : ! isCollapsed,
                {{ $assetTypes ? "'cursor-pointer' : ! isReordering," : '' }}
            }"
            @if ($assetTypes)
                x-on:click.self="! isReordering ? toggleCollapse() : null"
            @endif
        >
            <div
                class="flex grow items-center"
                @if ($assetTypes) x-on:click="! isReordering ? toggleCollapse() : null" @endif
            >
                <div class="mr-1 flex w-7 shrink-0 items-center gap-3">
                    <span class="relative" x-show="! isReordering">
                        <x-filament::icon
                            :class="'h-5 w-5' . ($assetTypes ? ' text-primary-600' : ' text-gray-400')"
                            :x-tooltip.raw="$widget->type?->name"
                            :icon="$widgetIcon"
                        />

                        @if ($assetTypes)
                            <x-filament::badge
                                :color="$hasPageAssets ? 'primary' : 'gray'"
                                :size="Size::ExtraSmall"
                                class="absolute -right-2 -top-2 inline-flex"
                            >
                                {{ $this->countWidgetAssets($containerKey, $widgetIndex) }}
                            </x-filament::badge>
                        @endif
                    </span>

                    <div
                        wire:loading.class="pointer-events-none opacity-40"
                        x-cloak
                        x-show="isReordering"
                        x-sort:handle
                        x-transition:enter="transition duration-150 ease-in-out"
                    >
                        <x-filament::icon-button
                            class="layout-container-widget-handle"
                            color="primary"
                            icon="heroicon-o-arrows-up-down"
                            size="sm"
                        />
                    </div>
                </div>

                <span class="text-sm text-gray-600 dark:text-gray-100">
                    <span
                        @class([
                            'font-medium',
                            'group-hover/widget:text-primary-600' => $assetTypes,
                        ])
                    >
                        {{ $widget->name }}
                    </span>

                    @if (! empty($containerWidget['meta']['name']))
                        -
                        <b>{{ $containerWidget['meta']['name'] }}</b>
                    @endif

                    @if ($title && $title !== $widget->name)
                        <br />
                        {{ $title }}
                    @endif
                </span>

                @if ($type)
                    <x-filament::badge size="xs" color="info">
                        {{ $type }}
                    </x-filament::badge>
                @endif

                @if ($image)
                    {{ $image->img(MediaConversionEnum::Thumbnail->value)->lazy()->attributes(['class' => 'ml-auto max-h-12 max-w-12 object-contain']) }}
                @endif
            </div>

            <div
                class="ml-auto grid shrink flex-wrap items-center justify-end gap-x-4 gap-y-2 md:flex"
                x-show="! isReordering"
            >
                @if ($assetTypes)
                    <x-filament::dropdown placement="bottom-end">
                        <x-slot name="trigger">
                            <x-filament::link
                                :iconSize="IconSize::Small"
                                :weight="FontWeight::Medium"
                                :size="Size::ExtraSmall"
                                color="primary"
                                icon="heroicon-c-plus-circle"
                                :outlined="true"
                            >
                                {{ __('capell-mosaic::button.assets') }}
                            </x-filament::link>
                        </x-slot>
                        @foreach ($assetTypes as $assetType)
                            <x-filament::dropdown.list>
                                <x-filament::dropdown.header
                                    class="cursor-default font-semibold"
                                    color="gray"
                                    :icon="CapellCore::getAsset($assetType)->getIcon()"
                                >
                                    {{ CapellCore::getAsset($assetType)->getLabel() }}
                                </x-filament::dropdown.header>
                                {{ ($this->selectAssetAction)(['containerKey' => $containerKey, 'widgetIndex' => $widgetIndex, 'type' => $assetType, 'types' => $assetTypes]) }}
                                {{ ($this->addAssetAction)(['containerKey' => $containerKey, 'widgetIndex' => $widgetIndex, 'type' => $assetType, 'types' => $assetTypes]) }}
                            </x-filament::dropdown.list>
                        @endforeach
                    </x-filament::dropdown>
                @endif

                <div class="fi-btn-group flex items-center">
                    {{ $editWidgetAction }}

                    <x-filament::dropdown
                        class="fi-btn-group-dropdown"
                        placement="bottom-end"
                        teleport
                    >
                        <x-slot name="trigger">
                            <x-filament::button
                                class="fi-btn-outlined"
                                icon="heroicon-o-ellipsis-vertical"
                                size="sm"
                                color="gray"
                                :label-sr-only="true"
                            />
                        </x-slot>

                        <x-filament::dropdown.list>
                            @if ($editLayoutWidgetAction?->isVisible())
                                {{ $editLayoutWidgetAction }}
                            @endif

                            @if ($togglePageAssetsAction?->isVisible())
                                {{ $togglePageAssetsAction }}
                            @endif

                            {{ ($this->duplicateWidgetAction)(['containerKey' => $containerKey, 'widgetIndex' => $widgetIndex]) }}

                            {{ ($this->removeWidgetAction)(['containerKey' => $containerKey, 'widgetIndex' => $widgetIndex]) }}
                        </x-filament::dropdown.list>
                    </x-filament::dropdown>
                </div>
            </div>
        </div>
    </div>

    @if ($assetTypes)
        <x-capell-mosaic::filament.layout-builder.assets
            :$containerKey
            :$hasPageAssets
            :$occurrence
            :$assetTypes
            :$widget
            :$widgetIndex
        />
    @endif
</div>
