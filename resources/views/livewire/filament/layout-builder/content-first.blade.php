@php
    $inventory = $this->contentInventory;
@endphp

<section
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
            <p class="text-sm font-medium text-gray-950 dark:text-white">
                {{ __('capell-layout-builder::message.content_inventory_empty') }}
            </p>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                {{ __('capell-layout-builder::message.content_inventory_empty_hint') }}
            </p>
        </div>
    @else
        <div class="space-y-5">
            @foreach ($inventory->groups as $group)
                <section
                    class="rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                    aria-labelledby="layout-content-group-{{ $group->key }}"
                >
                    <div
                        class="border-b border-gray-950/5 px-4 py-3 dark:border-white/10"
                    >
                        <h3
                            id="layout-content-group-{{ $group->key }}"
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

                    <div
                        class="divide-y divide-gray-950/5 dark:divide-white/10"
                    >
                        @foreach ($group->items as $item)
                            @php
                                $editElementAssetAction = ($this->editElementAssetAction)($item->editActionArguments);
                            @endphp

                            <article
                                id="layout-content-item-{{ md5($item->key) }}"
                                data-layout-content-item-key="{{ $item->key }}"
                                class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
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
                                            class="mt-1 line-clamp-2 text-sm text-gray-600 dark:text-gray-300"
                                        >
                                            {{ $item->summary }}
                                        </p>
                                    @endif

                                    <p
                                        class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        {{ $item->placementLabel }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    {{ $editElementAssetAction }}

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
    @endif
</section>
