<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;
@endphp

@props([
    'language' => Frontend::language(),
    'related',
    'key',
])

<div
    style="
        --slide-size: 100%;
        --slide-size-sm: 50%;
        --slide-size-lg: calc(100% / {{ min(3, $related->count()) }});
    "
    {{ $attributes->class(['@container/related hero-related mt-4 grid gap-x-8 gap-y-4 lg:mt-6 lg:flex']) }}
>
    @foreach ($related as $feature)
        {{-- format-ignore-start --}}
        @php
            $url = null;

            $linkedPage = $feature instanceof \Capell\Core\Models\Page ? $feature : $feature->linkedPage;

            if ($linkedPage) {
                $url = $linkedPage->pageUrl->full_url;
            }

            $linkText = $feature->getMeta('link_text');
        @endphp
        {{-- format-ignore-end --}}
        <div
            class="@container/item hero-related-item @md:basis-[var(--slide-size-sm)] @lg:shrink @lg:basis-[var(--slide-size-lg)] group min-w-0 shrink-0 grow-0 basis-[var(--slide-size)]"
        >
            <div
                class="@2xs/item:flex-nowrap flex flex-wrap items-center gap-x-4 gap-y-3"
            >
                @if ($feature->image)
                    {{-- format-ignore-start --}}
                    @php
                        $width = 200;
                        $height = floor($width * ($feature->image->height / $feature->image->width));
                    @endphp
                    {{-- format-ignore-end --}}
                @endif

                @if ($feature->translation)
                    <div
                        class="prose prose-sm dark:prose-invert grid h-full grow [&>:first-child]:mt-0 [&>:last-child]:mb-0"
                    >
                        @if ($feature->translation->title)
                            <p class="text-md @2xs/item:text-lg mb-1 leading-6">
                                @if ($url)
                                    <a
                                        href="{{ $url }}"
                                        wire:navigate
                                        class="text-link hover:text-primary-600 focus:text-primary-600 font-medium no-underline"
                                    >
                                        <strong class="font-semibold">
                                            {!! $feature->translation->title !!}
                                        </strong>
                                    </a>
                                @else
                                    <strong class="font-semibold">
                                        {!! $feature->translation->title !!}
                                    </strong>
                                @endif
                            </p>
                        @endif

                        <div
                            class="line-clamp-4 break-words font-medium leading-6 opacity-80"
                        >
                            {!! $feature->translation->summary !!}
                        </div>

                        @if ($linkText)
                            <a
                                class="text-link hover:text-primary focus:text-primary font-medium no-underline focus:underline"
                                href="{{ $url }}"
                                title="{{ e(strip_tags($feature->translation->title)) }}"
                                wire:navigate
                            >
                                {{ $linkText }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>

<?php
