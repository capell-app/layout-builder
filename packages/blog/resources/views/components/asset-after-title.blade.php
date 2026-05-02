<div class="@sm:flex-nowrap flex justify-between gap-x-4 gap-y-2">
    @if ($tags?->isNotEmpty())
        <x-capell-blog::page.tags
            :tags="$tags"
            class="mt-2 text-xs font-normal text-gray-500"
        />
    @endif

    @if ($publishDate && $publishDatePosition === 'bottom')
        {{ $publishDateOutput($publishDate, '@sm/item:sm mb-1 mt-auto') }}
    @endif
</div>
