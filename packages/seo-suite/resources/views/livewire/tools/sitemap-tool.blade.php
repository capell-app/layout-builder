@php
    use Capell\SeoSuite\Enums\SitemapCacheKey;
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Icons\Heroicon;

    $generatingSitemaps = cache()->has(SitemapCacheKey::Generating->value);
@endphp

<div>
    <button
        class="fi-dropdown-list-item fi-dropdown-list-item-color-gray flex w-full items-center gap-2 whitespace-nowrap rounded-md p-2 text-sm outline-none transition-colors duration-75 hover:bg-gray-50 focus:bg-gray-50 disabled:pointer-events-none disabled:opacity-70 dark:hover:bg-white/5 dark:focus:bg-white/5"
        type="button"
        wire:click="generate"
        wire:loading.attr="disabled"
        {{ $generatingSitemaps ? 'disabled' : '' }}
    >
        @if ($generatingSitemaps)
            @svg(Heroicon::OutlinedArrowPath->getIconForSize(IconSize::Small), [
                'class' => 'fi-dropdown-list-item-icon h-5 w-5 animate-spin text-gray-400 dark:text-gray-500',
            ])
        @else
            @svg(Heroicon::OutlinedGlobeAlt->getIconForSize(IconSize::Small), [
                'class' => 'fi-dropdown-list-item-icon h-5 w-5 text-gray-400 dark:text-gray-500',
                'wire:loading.remove.delay' => 1,
                'wire:target' => 'generate',
            ])

            @svg(Heroicon::OutlinedArrowPath->getIconForSize(IconSize::Small), [
                'class' => 'fi-dropdown-list-item-icon h-5 w-5 animate-spin text-gray-400 dark:text-gray-500',
                'wire:loading.delay' => 1,
                'wire:target' => 'generate',
            ])
        @endif
        {{ __('capell-admin::generic.sitemap_generate') }}
    </button>
</div>
