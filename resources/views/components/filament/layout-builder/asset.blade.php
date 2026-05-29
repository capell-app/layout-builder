@props([
    'containerKey',
    'description' => null,
    'index',
    'image' => null,
    'meta' => [],
    'name' => null,
    'occurrence',
    'pageId' => $this->page?->getKey(),
    'block',
    'blockAsset',
    'blockIndex',
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
    use Illuminate\Support\Js;
    use Illuminate\Support\Str;
    use Spatie\MediaLibrary\MediaCollections\Models\Media;

    $editBlockAssetArguments = [
        'containerKey' => $containerKey,
        'blockIndex' => $blockIndex,
        'index' => $index,
        'type' => $blockAsset->asset_type,
    ];
    $editBlockAssetClickHandler = '$wire.mountAction(\'editBlockAsset\', ' . Js::from($editBlockAssetArguments) . ')';

    if (! $blockAsset->asset) {
        throw new \RuntimeException("Asset of type [{$blockAsset->asset_type}] with ID [{$blockAsset->asset_id}] not found.");
    }

    $assetKey = "{$blockAsset->asset_type}.{$blockAsset->asset_id}";

    if (! $image) {
        $image = match (get_class($blockAsset->asset)) {
            Page::class => $blockAsset->asset->image,
            Media::class => $blockAsset->asset,
            default => $blockAsset->asset->image ?? null,
        };
    }

    $mediaCount = $blockAsset->asset->media?->count();

    $relatedCount = $blockAsset->asset->related?->count();

    $actionsCount = isset($blockAsset->asset->actions) ? count($blockAsset->asset->actions) : null;

    $label = '';

    if (! $name) {
        $name = $blockAsset->asset->name;
    }

    $label .= $name;

    if (! $description) {
        $description = '';

        if ($blockAsset->asset instanceof Page && $blockAsset->asset->ancestors?->isNotEmpty()) {
            $description .= $blockAsset->asset->ancestors->pluck('name')
                ->map(fn (string $name): string => Str::limit($name, 30))
                ->implode(' &raquo; ');
        }

        $description .= match (get_class($blockAsset->asset)) {
            Page::class, Section::class => $blockAsset->asset->translation?->title &&
            $blockAsset->asset->translation->title !== $blockAsset->asset->name
                ? $blockAsset->asset->translation->title
                : null,
            default => null,
        };
    }

    /** @var class-string<Site> $model */
    $model = Site::class;

    if ($model::totalSites() > 1) {
        if ($blockAsset->asset->hasAttribute('site_id') && $blockAsset->asset->site_id) {
            $description = $blockAsset->asset->site?->name . ($description ? ' - ' . $description : '');
        }
    }

    $plainLabel = trim(strip_tags((string) $label));
    $plainDescription = trim(strip_tags(str_replace('&raquo;', ' > ', (string) $description)));
    $editBlockAssetTooltip = __('capell-layout-builder::button.edit_asset_type', ['type' => $blockAsset->asset_type]);
    $moveAssetUpAction = ($this->moveAssetUpAction)([
        'containerKey' => $containerKey,
        'blockIndex' => $blockIndex,
        'assetIndex' => $index,
    ]);
    $moveAssetDownAction = ($this->moveAssetDownAction)([
        'containerKey' => $containerKey,
        'blockIndex' => $blockIndex,
        'assetIndex' => $index,
    ]);

    $icon = CapellCore::getAsset($blockAsset->asset_type)->getIcon();
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
                wire:key="{{ 'selectedRecords' . $containerKey . '-' . $blockIndex . '-' . $assetKey }}"
                x-model="selectedRecords['{{ $containerKey }}'][{{ $blockIndex }}]"
                x-show="! isBlockReorderingResources('{{ $containerKey }}', {{ $blockIndex }})"
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
                x-show="isBlockReorderingResources('{{ $containerKey }}', {{ $blockIndex }})"
                x-cloak
                tabindex="-1"
                aria-hidden="true"
            >
                @svg('heroicon-o-arrows-up-down', 'h-5 w-5')
            </button>
        </label>

        <button
            type="button"
            data-layout-asset-action="editBlockAsset"
            x-on:click="{{ $editBlockAssetClickHandler }}"
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
                            'x-tooltip.raw' => $editBlockAssetTooltip,
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
                                class="pointer-events-none absolute -top-2 -right-2"
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
                            class="pointer-events-none absolute -top-2 -right-2"
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
                            class="pointer-events-none absolute -top-2 -right-2"
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
                    $resource = GetResourceFromBlueprintAction::run(ucfirst($blockAsset->asset_type), $blockAsset->asset->type);
                @endphp

                @if ($resource)
                    <x-filament::dropdown.list.item
                        icon="heroicon-o-arrow-top-right-on-square"
                        target="_blank"
                        tag="a"
                        :href="$resource::getUrl('edit', ['record' => $blockAsset->asset->getKey()])"
                    >
                        {{ __('capell-layout-builder::button.edit_asset_type', ['type' => $blockAsset->asset_type]) }}
                    </x-filament::dropdown.list.item>
                @endif
            </x-filament::dropdown.list>
        </x-filament::dropdown>
    </div>
</div>
