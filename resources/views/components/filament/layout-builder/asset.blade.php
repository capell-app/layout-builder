@props([
    'containerKey',
    'description' => null,
    'index',
    'image' => null,
    'meta' => [],
    'name' => null,
    'occurrence',
    'pageId' => $this->page?->getKey(),
    'element',
    'elementAsset',
    'elementIndex',
])
{{-- format-ignore-start --}}
@php
    use Capell\Admin\Facades\CapellAdmin;
    use Capell\Core\Actions\GetResourceFromBlueprintAction;
    use Capell\Core\Enums\MediaConversionEnum;
    use Capell\Core\Facades\CapellCore;
    use Capell\Core\Models\Page;
    use Capell\Core\Models\Site;
    use Filament\Support\Contracts\ScalableIcon;
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Enums\Size;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Str;
    use Spatie\MediaLibrary\MediaCollections\Models\Media;

    $editElementAssetArguments = [
        'containerKey' => $containerKey,
        'elementIndex' => $elementIndex,
        'index' => $index,
        'type' => $elementAsset->asset_type,
    ];

    if (! $elementAsset->asset) {
        throw new \RuntimeException("Asset of type [{$elementAsset->asset_type}] with ID [{$elementAsset->asset_id}] not found.");
    }

    $assetKey = "{$elementAsset->asset_type}.{$elementAsset->asset_id}";

    if (! $image) {
        $image = match (get_class($elementAsset->asset)) {
            Page::class => $elementAsset->asset->image,
            Media::class => $elementAsset->asset,
            default => $elementAsset->asset->image ?? null,
        };
    }

    $mediaCount = $elementAsset->asset->media?->count();

    $relatedCount = $elementAsset->asset->related?->count();

    $actionsCount = isset($elementAsset->asset->actions) ? count($elementAsset->asset->actions) : null;

    $label = '';

    if (! $name) {
        $name = $elementAsset->asset->name;
    }

    $label .= $name;

    if (! $description) {
        $description = '';

        if ($elementAsset->asset instanceof Page && $elementAsset->asset->ancestors?->isNotEmpty()) {
            $description .= $elementAsset->asset->ancestors->pluck('name')
                ->map(fn (string $name): string => Str::limit($name, 30))
                ->implode(' &raquo; ');
        }

        $description .= match (get_class($elementAsset->asset)) {
            Page::class, Section::class => $elementAsset->asset->translation?->title &&
            $elementAsset->asset->translation->title !== $elementAsset->asset->name
                ? $elementAsset->asset->translation->title
                : null,
            default => null,
        };
    }

    /** @var class-string<Site> $model */
    $model = Site::class;

    if ($model::totalSites() > 1) {
        if ($elementAsset->asset->hasAttribute('site_id') && $elementAsset->asset->site_id) {
            $description = $elementAsset->asset->site?->name . ($description ? ' - ' . $description : '');
        }
    }

    $plainLabel = trim(strip_tags((string) $label));
    $plainDescription = trim(strip_tags(str_replace('&raquo;', ' > ', (string) $description)));
    $editElementAssetTooltip = __('capell-layout-builder::button.edit_asset_type', ['type' => $elementAsset->asset_type]);
    $moveAssetUpAction = ($this->moveAssetUpAction)([
        'containerKey' => $containerKey,
        'elementIndex' => $elementIndex,
        'assetIndex' => $index,
    ]);
    $moveAssetDownAction = ($this->moveAssetDownAction)([
        'containerKey' => $containerKey,
        'elementIndex' => $elementIndex,
        'assetIndex' => $index,
    ]);

    $icon = CapellCore::getAsset($elementAsset->asset_type)->getIcon();
    if ($icon instanceof ScalableIcon) {
        $icon = $icon->getIconForSize(IconSize::Small);
    } elseif ($icon instanceof BackedEnum) {
        $icon = $icon->value;
    }
@endphp
{{-- format-ignore-end --}}
<div
    x-sort:item="{{ $index }}"
    wire:key="{{ $assetKey }}"
    {{ $attributes->class(['flex gap-4 pr-4']) }}
>
    <div class="flex flex-1 flex-grow items-center">
        <label
            x-on:click.stop
            class="group/asset flex h-full w-12 cursor-pointer items-center justify-center"
        >
            <input
                type="checkbox"
                class="group-hover/asset:border-primary-500 group/asset-focus:border-primary-500 text-primary-600 focus:border-primary-500 focus:ring-primary-500 dark:checked:bg-primary-500 ml-1 h-4 w-4 cursor-pointer rounded border-gray-600 shadow-sm transition duration-75 focus:ring-2 disabled:opacity-70 dark:border-gray-400 dark:bg-gray-700"
                value="{{ $assetKey }}"
                aria-label="{{ __('capell-layout-builder::button.select_asset_record', ['asset' => $plainLabel]) }}"
                wire:key="{{ 'selectedRecords' . $containerKey . '-' . $elementIndex . '-' . $assetKey }}"
                x-model="selectedRecords['{{ $containerKey }}'][{{ $elementIndex }}]"
                x-show="! isElementReorderingResources('{{ $containerKey }}', {{ $elementIndex }})"
                wire:loading.remove
                wire:target="mountAction"
            />

            <x-filament::loading-indicator
                class="text-primary-500 h-5 w-5"
                wire:loading
                wire:target="mountAction"
                :wire:loading.delay="config('filament.livewire_loading_delay', 'default')"
            />

            <button
                type="button"
                class="hover:text-primary-600 dark:hover:text-primary-400 focus:text-primary-600 dark:focus:text-primary-400 cursor-pointer text-gray-400 transition dark:text-gray-500"
                wire:loading.class="pointer-events-none opacity-40"
                x-sort:handle
                x-show="isElementReorderingResources('{{ $containerKey }}', {{ $elementIndex }})"
                x-cloak
                tabindex="-1"
                aria-hidden="true"
            >
                @svg('heroicon-o-arrows-up-down', 'h-5 w-5')
            </button>
        </label>

        <button
            type="button"
            data-layout-asset-action="editElementAsset"
            x-on:click="$wire.mountAction('editElementAsset', @js($editElementAssetArguments))"
            @class([
                'group/asset flex w-full cursor-pointer items-center gap-x-4 text-left',
                'lg:!grid lg:grid-cols-4 lg:gap-4' => $image,
            ])
        >
            <div @class(['py-2.5', 'lg:col-span-3' => $image])>
                <div
                    class="group-hover/asset:text-primary-600 dark:group-hover/asset:text-primary-400 line-clamp-1 text-sm text-gray-800 dark:text-gray-100"
                >
                    {{ $plainLabel }}

                    @svg($icon,
                        [
                            'class' => 'group-hover/asset:text-primary-500 dark:group-hover/asset:text-primary-400 inline h-4 w-4 align-text-bottom text-gray-400 dark:text-gray-500',
                            'x-tooltip.raw' => $editElementAssetTooltip,
                        ])
                </div>

                @if ($plainDescription !== '' && $plainDescription !== $plainLabel)
                    <div
                        class="mt-0.5 line-clamp-2 text-xs leading-tight text-gray-600 dark:text-gray-300"
                    >
                        {{ $plainDescription }}
                    </div>
                @endif
            </div>

            <div
                class="flex shrink-0 grow items-center justify-end gap-x-6 gap-y-2 py-1.5"
            >
                @if ($mediaCount || $image)
                    <span class="relative">
                        @if ($image)
                            {{ $image->img(MediaConversionEnum::Thumbnail->value)->lazy()->attributes(['class' => 'bg-gray-100 dark:bg-gray-800 h-8 w-8 ml-auto object-contain rounded']) }}
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
        </button>
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
                    :label="__('capell-layout-builder::button.asset_actions', ['asset' => $plainLabel])"
                />
            </x-slot>

            <x-filament::dropdown.list>
                @if ($moveAssetUpAction?->isVisible())
                    {{ $moveAssetUpAction }}
                @endif

                @if ($moveAssetDownAction?->isVisible())
                    {{ $moveAssetDownAction }}
                @endif

                @php
                    $resource = GetResourceFromBlueprintAction::run(ucfirst($elementAsset->asset_type), $elementAsset->asset->type);
                @endphp

                @if ($resource)
                    <x-filament::dropdown.list.item
                        icon="heroicon-o-arrow-top-right-on-square"
                        target="_blank"
                        tag="a"
                        :href="$resource::getUrl('edit', ['record' => $elementAsset->asset->getKey()])"
                    >
                        {{ __('capell-layout-builder::button.edit_asset_type', ['type' => $elementAsset->asset_type]) }}
                    </x-filament::dropdown.list.item>
                @endif
            </x-filament::dropdown.list>
        </x-filament::dropdown>
    </div>
</div>
