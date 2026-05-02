@php
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Icons\Heroicon;

    $workspace = $this->workspace;
@endphp

<div>
    @if ($workspace !== null)
        <div
            class="border-warning-500/30 bg-warning-50 text-warning-800 dark:bg-warning-950/50 dark:text-warning-200 sticky top-0 z-30 flex items-center gap-3 border-b px-4 py-2 text-sm"
        >
            @svg(Heroicon::OutlinedBeaker->getIconForSize(IconSize::Small), 'h-4 w-4 flex-shrink-0')

            <span class="flex-1 truncate">
                {{ __('capell-admin::workspace.banner.editing_in', ['name' => $workspace->name]) }}
            </span>

            @if ($workspace->color !== null)
                <span
                    class="inline-block h-2.5 w-2.5 flex-shrink-0 rounded-full"
                    style="background-color: {{ $workspace->color }}"
                ></span>
            @endif

            <button
                class="font-medium underline decoration-dotted hover:no-underline"
                type="button"
                wire:click="exitToLive"
            >
                {{ __('capell-admin::workspace.banner.exit') }}
            </button>
        </div>
    @endif
</div>
