@php
    use Illuminate\Support\Js;
    use Illuminate\Support\Str;

    $inventory = $this->contentInventory;
@endphp

<section
    x-data="{
        search: '',
        normalize(value) {
            return (value || '')
                .toString()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
        },
        hasActiveSearch() {
            return this.search.trim() !== ''
        },
        itemMatches(block) {
            return (
                ! this.hasActiveSearch() ||
                this.normalize(block.dataset.layoutContentSearch).includes(
                    this.normalize(this.search),
                )
            )
        },
        groupMatches(block) {
            return (
                ! this.hasActiveSearch() ||
                Array.from(
                    block.querySelectorAll('[data-layout-content-item-key]'),
                ).some((item) => this.itemMatches(item))
            )
        },
        visibleItems() {
            return Array.from(
                this.$refs.contentItems?.querySelectorAll(
                    '[data-layout-content-item-key]',
                ) || [],
            ).filter((item) => this.itemMatches(item)).length
        },
    }"
    class="rounded-lg bg-gray-50 p-4 dark:bg-gray-950"
    aria-labelledby="layout-content-editor-heading"
>
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2
                id="layout-content-editor-heading"
                class="text-base font-semibold text-gray-950 dark:text-white"
            >
                {{ __('capell-layout-builder::generic.content_first_editor') }}
            </h2>
        </div>

        @if ($this->canEditLayout())
            <x-filament::button
                color="gray"
                icon="heroicon-o-adjustments-horizontal"
                size="sm"
                wire:click="showAdvancedLayout"
            >
                {{ __('capell-layout-builder::button.advanced_layout') }}
            </x-filament::button>
        @endif
    </div>

    @if ($inventory->itemCount === 0)
        <div
            class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-900"
        >
            <div
                class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400"
            >
                @svg('heroicon-o-document-text', 'h-5 w-5')
            </div>
            <p class="text-sm font-medium text-gray-950 dark:text-white">
                {{ __('capell-layout-builder::message.content_inventory_empty') }}
            </p>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                {{ __('capell-layout-builder::message.content_inventory_empty_hint') }}
            </p>
        </div>
    @else
        <div class="space-y-5">
            <div
                class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
            >
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="min-w-0">
                        <p
                            class="text-sm font-medium text-gray-950 dark:text-white"
                        >
                            {{ trans_choice('capell-layout-builder::message.content_inventory_count', $inventory->itemCount, ['count' => $inventory->itemCount]) }}
                        </p>
                        <p
                            class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
                        >
                            {{ __('capell-layout-builder::message.content_inventory_search_hint') }}
                        </p>
                    </div>

                    <label class="relative block w-full sm:max-w-xs">
                        <span class="sr-only">
                            {{ __('capell-layout-builder::form.search_content_inventory') }}
                        </span>
                        @svg('heroicon-o-magnifying-glass', 'pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500')
                        <input
                            type="search"
                            x-model.debounce.150ms="search"
                            data-layout-content-search-input
                            class="focus:border-primary-500 focus:ring-primary-500 h-9 w-full rounded-md border border-gray-300 bg-white py-1.5 pl-9 pr-9 text-sm text-gray-950 shadow-sm outline-none transition placeholder:text-gray-400 focus:ring-1 dark:border-gray-700 dark:bg-gray-950 dark:text-white dark:placeholder:text-gray-500"
                            placeholder="{{ __('capell-layout-builder::form.search_content_inventory') }}"
                            autocomplete="off"
                        />
                        <button
                            type="button"
                            x-show="hasActiveSearch()"
                            x-cloak
                            x-on:click="search = ''"
                            class="hover:text-primary-600 focus:text-primary-600 dark:hover:text-primary-400 dark:focus:text-primary-400 absolute right-2 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded text-gray-400 transition dark:text-gray-500"
                            aria-label="{{ __('capell-layout-builder::button.clear_content_inventory_search') }}"
                        >
                            @svg('heroicon-o-x-mark', 'h-4 w-4')
                        </button>
                    </label>
                </div>
            </div>

            <div x-ref="contentItems" class="space-y-5">
                @foreach ($inventory->groups as $group)
                    @php
                        $groupDomKey = md5($inventory->signature . '|group|' . $group->key);
                    @endphp

                    <section
                        wire:key="layout-content-group-{{ $groupDomKey }}"
                        x-show="groupMatches($el)"
                        x-transition.opacity.duration.150ms
                        class="rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                        aria-labelledby="layout-content-group-{{ $groupDomKey }}"
                    >
                        <div
                            class="flex flex-col gap-2 border-b border-gray-950/5 px-4 py-3 sm:flex-row sm:items-start sm:justify-between dark:border-white/10"
                        >
                            <div class="min-w-0">
                                <h3
                                    id="layout-content-group-{{ $groupDomKey }}"
                                    class="text-sm font-semibold text-gray-950 dark:text-white"
                                >
                                    {{ $group->label }}
                                </h3>

                                @if ($group->summary)
                                    <p
                                        class="mt-1 text-xs text-gray-600 dark:text-gray-300"
                                    >
                                        {{ $group->summary }}
                                    </p>
                                @endif
                            </div>

                            <x-filament::badge
                                color="gray"
                                size="sm"
                                class="shrink-0"
                            >
                                {{ trans_choice('capell-layout-builder::message.content_inventory_group_count', count($group->items), ['count' => count($group->items)]) }}
                            </x-filament::badge>
                        </div>

                        <div
                            class="divide-y divide-gray-950/5 dark:divide-white/10"
                        >
                            @foreach ($group->items as $item)
                                @php
                                    $itemDomKey = md5($inventory->signature . '|' . $item->key);
                                    $itemSearchText = Str::of(collect([
                                        $item->label,
                                        $item->summary,
                                        $item->typeLabel,
                                        $item->placementLabel,
                                        $item->containerLabel,
                                        $item->blockLabel,
                                        $item->assetType,
                                        (string) $item->assetId,
                                    ])->filter(fn (?string $value): bool => filled($value))->implode(' '))->squish();
                                    $editBlockAssetClickHandler = '$wire.mountAction(\'editBlockAsset\', ' . Js::from($item->editActionArguments) . ')';
                                @endphp

                                <article
                                    id="layout-content-item-{{ $itemDomKey }}"
                                    wire:key="layout-content-item-{{ $itemDomKey }}"
                                    data-layout-content-item-key="{{ $item->key }}"
                                    data-layout-content-search="{{ $itemSearchText }}"
                                    x-show="itemMatches($el)"
                                    x-transition.opacity.duration.150ms
                                    class="group/content-item flex flex-col gap-3 px-4 py-3 transition focus-within:bg-gray-50 hover:bg-gray-50 sm:flex-row sm:items-center sm:justify-between dark:focus-within:bg-white/5 dark:hover:bg-white/5"
                                    tabindex="-1"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <h4
                                                class="line-clamp-1 text-sm font-medium text-gray-950 dark:text-white"
                                            >
                                                {{ $item->label }}
                                            </h4>

                                            <x-filament::badge
                                                color="gray"
                                                size="sm"
                                            >
                                                {{ $item->typeLabel }}
                                            </x-filament::badge>

                                            @if ($item->isReused)
                                                <x-filament::badge
                                                    color="warning"
                                                    size="sm"
                                                >
                                                    {{ __('capell-layout-builder::message.content_item_reused') }}
                                                </x-filament::badge>
                                            @endif
                                        </div>

                                        @if ($item->summary)
                                            <p
                                                class="mt-1 line-clamp-2 text-sm leading-6 text-gray-600 dark:text-gray-300"
                                            >
                                                {{ $item->summary }}
                                            </p>
                                        @endif

                                        <p
                                            class="mt-1 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            @svg('heroicon-o-map-pin', 'h-3.5 w-3.5 shrink-0')
                                            {{ $item->placementLabel }}
                                        </p>
                                    </div>

                                    <div
                                        class="flex shrink-0 items-center gap-2"
                                    >
                                        @if ($this->canEditContent())
                                            <x-filament::button
                                                color="primary"
                                                icon="heroicon-o-pencil"
                                                size="xs"
                                                type="button"
                                                data-layout-content-action="editBlockAsset"
                                                x-on:click="{{ $editBlockAssetClickHandler }}"
                                            >
                                                {{ __('capell-admin::button.edit') }}
                                            </x-filament::button>
                                        @endif

                                        @if ($this->canEditLayout())
                                            <x-filament::icon-button
                                                color="gray"
                                                icon="heroicon-o-adjustments-horizontal"
                                                size="sm"
                                                :label="__('capell-layout-builder::button.advanced_layout')"
                                                wire:click="showAdvancedLayout({{ Js::from($item->key) }})"
                                            />
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            <div
                x-show="hasActiveSearch() && visibleItems() === 0"
                x-cloak
                data-layout-content-search-empty
                class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-900"
            >
                <div
                    class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                >
                    @svg('heroicon-o-magnifying-glass', 'h-5 w-5')
                </div>
                <p class="text-sm font-medium text-gray-950 dark:text-white">
                    {{ __('capell-layout-builder::message.content_inventory_search_empty') }}
                </p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    {{ __('capell-layout-builder::message.content_inventory_search_empty_hint') }}
                </p>
            </div>
        </div>
    @endif
</section>
