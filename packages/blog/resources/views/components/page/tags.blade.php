@php
    use Capell\Frontend\Facades\Frontend;
    use Filament\Support\Icons\Heroicon;

    $language = Frontend::language();
@endphp

@props([
    'linkClass' => '',
    'tagPage',
    'tags',
    'tagIcon' => 'heroicon-' . Heroicon::OutlinedTag->value,
])

@if ($tags && $tagPage)
    <div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
        @if ($tagIcon)
            @svg($tagIcon, 'inline-block h-6 w-6 shrink-0 text-gray-400')
        @endif

        <div class="flex flex-wrap gap-x-2 gap-y-1.5">
            @foreach ($tags as $tag)
                @php($url = $tagPage->pageUrl->full_url . '/' . $tag->getTranslation('slug', $language->code))
                <x-capell-blog::tag :$url wire:navigate>
                    {{ $tag->getTranslation('name', $language->code) }}
                </x-capell-blog::tag>
            @endforeach
        </div>
    </div>
@endif
