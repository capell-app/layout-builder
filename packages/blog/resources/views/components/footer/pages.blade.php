<?php

declare(strict_types=1);

?>

@props([
    'headingClass',
    'pages',
])

<div {{ $attributes->class(['footer-pages xl:w-[25%]']) }}>
    <h3 class="{{ $headingClass }} mb-4">
        {{ __('Recent Articles') }}
    </h3>
    <div class="space-y-3 lg:space-y-4">
        @forelse ($pages as $page)
            @php
                $publishDate = $page->getPublishDate();
                $url = $page->pageUrl->full_url;
            @endphp

            <div @class(['grid', 'grid-cols-4 gap-x-3' => $page->image])>
                @if ($page->image)
                    <a
                        href="{{ $url }}"
                        title="{{ htmlspecialchars(strip_tags($page->translation->label)) }}"
                        wire:navigate
                    >
                        <x-capell::media
                            :square="true"
                            :media="$page->image"
                            width="180"
                            height="180"
                            class="object-cover"
                        />
                    </a>
                @endif

                <a
                    href="{{ $url }}"
                    @class([
                        'focus:text-primary flex flex-col justify-center gap-y-1 text-inherit hover:text-gray-400',
                        'col-span-3 py-0.5' => $page->image,
                    ])
                    wire:navigate
                >
                    <span class="text-md font-medium">
                        {!! $page->getTranslation('label') !!}
                    </span>
                    <time
                        class="float-right mt-0.5 whitespace-nowrap text-xs font-light leading-none tracking-wide opacity-80"
                        title="{{ __('capell-frontend::generic.visible_from', ['date' => $publishDate->format(config('capell-frontend.date_format'))]) }}"
                        datetime="{{ $publishDate->toW3cString() }}"
                    >
                        {{ $publishDate->format(config('capell-frontend.date_format')) }}
                    </time>
                </a>
            </div>
        @empty
            <div class="text-sm font-medium tracking-tight">
                {{ __('capell-frontend::generic.no_articles') }}
            </div>
        @endforelse
    </div>
</div>

<?php
