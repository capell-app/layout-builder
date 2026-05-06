@php
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Icons\Heroicon;

    $current = $this->currentWorkspace;
    $publishingStudio = $this->publishingStudio;
    $createUrl = $this->createUrl;
    $triggerLabel = $current?->name ?? __('capell-admin::workspace.switcher.live');
    $triggerColor = $current?->color;
@endphp

<x-filament::dropdown
    placement="bottom-end"
    x-on:close-dropdown="if ($event.detail.id === 'workspace-switcher-dropdown') close()"
>
    <x-slot name="trigger">
        <button
            class="flex h-8 flex-shrink-0 items-center gap-2 rounded-md px-2 text-sm hover:bg-gray-100 dark:hover:bg-white/5"
            type="button"
            title="{{ __('capell-admin::workspace.switcher.title') }}"
        >
            @if ($triggerColor)
                <span
                    class="inline-block h-2.5 w-2.5 flex-shrink-0 rounded-full"
                    style="background-color: {{ $triggerColor }}"
                ></span>
            @else
                @svg(Heroicon::OutlinedGlobeAlt->getIconForSize(IconSize::Small), 'h-4 w-4 text-gray-400 dark:text-gray-500')
            @endif

            <span class="max-w-[10rem] truncate font-medium">
                {{ $triggerLabel }}
            </span>
        </button>
    </x-slot>

    <x-filament::dropdown.header>
        {{ __('capell-admin::workspace.switcher.header') }}
    </x-filament::dropdown.header>

    <x-filament::dropdown.list>
        <button
            class="fi-dropdown-list-item fi-dropdown-list-item-color-gray flex w-full items-center gap-2 whitespace-nowrap rounded-md p-2 text-sm outline-none transition-colors duration-75 hover:bg-gray-50 focus:bg-gray-50 dark:hover:bg-white/5 dark:focus:bg-white/5"
            type="button"
            wire:click="returnToLive"
            @disabled($current === null)
        >
            @svg(Heroicon::OutlinedGlobeAlt->getIconForSize(IconSize::Small), 'fi-dropdown-list-item-icon h-5 w-5 text-gray-400 dark:text-gray-500')
            {{ __('capell-admin::workspace.switcher.live') }}
            @if ($current === null)
                @svg(Heroicon::Check->getIconForSize(IconSize::Small), 'text-primary-500 ml-auto h-4 w-4')
            @endif
        </button>

        @foreach ($publishingStudio as $workspace)
            <button
                class="fi-dropdown-list-item fi-dropdown-list-item-color-gray flex w-full items-center gap-2 whitespace-nowrap rounded-md p-2 text-sm outline-none transition-colors duration-75 hover:bg-gray-50 focus:bg-gray-50 dark:hover:bg-white/5 dark:focus:bg-white/5"
                type="button"
                wire:click="switchTo({{ $workspace->id }})"
            >
                <span
                    class="inline-block h-2.5 w-2.5 flex-shrink-0 rounded-full"
                    @style(['background-color: ' . $workspace->color => $workspace->color !== null])
                ></span>
                <span class="truncate">{{ $workspace->name }}</span>
                @if ($current?->id === $workspace->id)
                    @svg(Heroicon::Check->getIconForSize(IconSize::Small), 'text-primary-500 ml-auto h-4 w-4')
                @endif
            </button>
        @endforeach
    </x-filament::dropdown.list>

    @if ($createUrl !== null)
        <x-filament::dropdown.list>
            <x-filament::dropdown.list.item
                :href="$createUrl"
                tag="a"
                :icon="Heroicon::OutlinedPlusCircle"
            >
                {{ __('capell-admin::workspace.switcher.manage') }}
            </x-filament::dropdown.list.item>
        </x-filament::dropdown.list>
    @endif
</x-filament::dropdown>
