<?php

declare(strict_types=1);

?>

@props([
    'containerKey',
    'description' => null,
    'index',
    'image' => null,
    'meta' => [],
    'name' => null,
    'occurrence',
    'pageId' => $this->page_id,
    'widget',
    'widgetAsset',
    'widgetIndex',
])
@php
    use Capell\Admin\Facades\CapellAdmin;
    use Capell\Core\Actions\GetResourceFromTypeAction;
    use Capell\Core\Enums\ModelEnum;
    use Capell\Core\Facades\CapellCore;
    use Capell\Core\Models\Page;
    use Capell\Layout\Models\Content;
    use Filament\Actions\Action;
    use Filament\Support\Contracts\ScalableIcon;
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Enums\Size;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Str;
    use Spatie\MediaLibrary\MediaCollections\Models\Media;

    /** @var Action $editWidgetAssetAction */
    $editWidgetAssetAction = ($this->editWidgetAssetAction)([
        'containerKey' => $containerKey,
        'widgetIndex' => $widgetIndex,
        'index' => $index,
        'type' => $widgetAsset->asset_type,
    ]);

    /** @var Model $asset */
    $asset = $widgetAsset->asset;

    if (! $asset?->getKey()) {
        throw new \RuntimeException("Asset of type [{$widgetAsset->asset_type}] with ID [{$widgetAsset->asset_id}] not found.");
    }

    $assetKey = "{$widgetAsset->asset_type}.{$widgetAsset->asset_id}";

    if (! $image) {
        $image = match (get_class($asset)) {
            Page::class, Content::class => $asset->image,
            Media::class => $asset,
            default => null,
        };
    }

    $mediaCount = match (get_class($asset)) {
        Content::class => $asset->media->count(),
        default => null,
    };

    $relatedCount = match (get_class($asset)) {
        Content::class => $asset->related->count(),
        default => null,
    };

    $actionsCount = match (get_class($asset)) {
        Content::class => count($asset->actions),
        default => null,
    };

    $label = '';

    if (! $name) {
        $name = $asset->name;
    }

    $label .= $name;

    if (! $description) {
        $description = '';

        if ($asset->ancestors?->isNotEmpty()) {
            $description .= $asset->ancestors->pluck('name')
                ->map(fn ($item) => Str::limit($item, 30))
                ->implode(' &raquo; ');
        }

        $description .= match (get_class($asset)) {
            Page::class, Content::class => $asset->translation?->title &&
            $asset->translation->title !== $asset->name
                ? $asset->translation->title
                : null,
            default => null,
        };
    }

    if (CapellCore::getModel(ModelEnum::Site)::totalSites() > 1) {
        if ($asset->hasAttribute('site_id') && $asset->site_id) {
            $description = $asset->site?->name . ($description ? ' - ' . $description : '');
        }
    }

    $icon = $editWidgetAssetAction->getIcon();
    if ($icon instanceof ScalableIcon) {
        $icon = $icon->getIconForSize(IconSize::Small);
    } elseif ($icon instanceof BackedEnum) {
        $icon = $icon->value;
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
            class="group/asset flex h-full w-12 cursor-pointer items-center justify-center"
        >
            <x-capell-admin::forms.checkbox
                class="group-hover/asset:border-primary-500 group/asset-focus:border-primary-500 ml-1 h-4 w-4 cursor-pointer border-gray-600"
                :label="__('tables::table.fields.bulk_select_asset.label', ['key' => $name])"
                :value="$assetKey"
                :wire:key="'selectedRecords' . $containerKey . '-' . $widgetIndex . '-' . $assetKey"
                x-model="selectedRecords['{{ $containerKey }}'][{{ $widgetIndex }}]"
                :x-show="'! isWidgetReorderingResources(\'' . $containerKey . '\', ' . $widgetIndex . ')'"
                wire:loading.remove
                wire:target="{{ $editWidgetAssetAction->getLivewireClickHandler() }}"
            />

            <x-filament::loading-indicator
                class="text-primary-500 h-5 w-5"
                wire:loading
                :wire:target="$editWidgetAssetAction->getLivewireClickHandler()"
                :wire:loading.delay="config('filament.livewire_loading_delay', 'default')"
            />

            <button
                type="button"
                class="hover:text-primary-600 dark:hover:text-primary-400 focus:text-primary-600 dark:focus:text-primary-400 cursor-pointer text-gray-400 transition dark:text-gray-500"
                wire:loading.class="pointer-events-none opacity-40"
                x-sort:handle
                x-show="isWidgetReorderingResources('{{ $containerKey }}', {{ $widgetIndex }})"
                x-cloak
            >
                @svg('heroicon-o-arrows-up-down', 'h-5 w-5')
            </button>
        </label>

        <div
            @class([
                'group/asset flex w-full cursor-pointer items-center gap-x-4',
                'lg:!grid lg:grid-cols-4 lg:gap-4' => $image,
            ])
        >
            <div @class(['py-2.5', 'lg:col-span-3' => $image])>
                <div
                    class="group-hover/asset:text-primary-600 dark:group-hover/asset:text-primary-400 line-clamp-1 text-sm text-gray-800 dark:text-gray-100"
                >
                    {!! $label !!}

                    @svg($icon,
                        [
                            'class' => 'group-hover/asset:text-primary-500 dark:group-hover/asset:text-primary-400 inline h-4 w-4 align-text-bottom text-gray-400 dark:text-gray-500',
                            'x-tooltip.raw' => $editWidgetAssetAction->getTooltip(),
                        ])
                </div>

                @if (! empty($description) && ! $description !== $name)
                    <div
                        class="mt-0.5 line-clamp-2 text-xs leading-tight text-gray-600 dark:text-gray-300"
                    >
                        {!! $description !!}
                    </div>
                @endif
            </div>

            <div
                class="flex shrink-0 grow items-center justify-end gap-x-6 gap-y-2 py-1.5"
            >
                @if ($mediaCount || $image)
                    <span class="relative">
                        @if ($image)
                            {{ $image->img('thumb')->lazy()->attributes(['class' => 'bg-gray-100 dark:bg-gray-800 max-h-10 max-w-10 ml-auto object-contain']) }}
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
                            icon="heroicon-c-arrow-down-tray"
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
                    :href="GetResourceFromTypeAction::run(ucfirst($widgetAsset->asset_type), $asset->type)::getUrl('edit', ['record' => $asset->getKey()])"
                >
                    {{ __('capell-layout::button.edit_asset_type', ['type' => $widgetAsset->asset_type]) }}
                </x-filament::dropdown.list.item>
            </x-filament::dropdown.list>
        </x-filament::dropdown>
    </div>
</div>

<?php
