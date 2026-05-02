@props([
    'headingClass' => null,
    'linkClass' => 'focus:bg-primary inline-flex items-center rounded-full bg-gray-600/75 px-3 py-2 text-sm font-medium leading-none tracking-wide text-[var(--color-footer)] no-underline hover:text-gray-400 focus:text-white',
])

<div {{ $attributes->class(['footer-tags xl:w-[20%]']) }}>
    <div class="{{ $headingClass }} mb-4">
        {{ __('Tags') }}
    </div>

    @if ($tags->isNotEmpty())
        <div class="flex flex-wrap gap-2">
            @foreach ($tags as $tag)
                @php($url = $tag->getUrl($tagPage, $language))
                <x-capell-blog::tag
                    :$url
                    wire:navigate
                    color="dark"
                    size="xs"
                    class="footer-tag"
                >
                    {{ $tag->getTranslation('name', $language->code) }}
                    <x-slot:count>
                        ({{ $tag->taggables_count }})
                    </x-slot>
                </x-capell-blog::tag>
            @endforeach
        </div>
    @endif
</div>
