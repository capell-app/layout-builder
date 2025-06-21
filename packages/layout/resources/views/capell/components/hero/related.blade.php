<?php

declare(strict_types=1);

?>

@props([
    'language' => Capell\Frontend\Facades\Frontend::getLanguage(),
    'features',
    'key',
])

<div
    style="
        --slide-size: 100%;
        --slide-size-sm: 50%;
        --slide-size-lg: calc(100% / {{ min(3, $features->count()) }});
    "
    {{ $attributes->class(['@container/related mt-4 grid gap-x-8 gap-y-4 lg:mt-6 lg:flex']) }}
>
    @foreach ($features as $feature)
        @php
            $url = null;
            if ($feature->page) {
                $pageUrl = Capell\Frontend\Services\Loader\PageLoader::getPageUrlById(
                    pageId: $feature->page->id,
                    site: $feature->page->site,
                    language: $language,
                );

                if ($pageUrl) {
                    $url = $pageUrl->full_url;
                }
            }
        @endphp

        <div
            class="@container/item @md:basis-[var(--slide-size-sm)] @lg:shrink @lg:basis-[var(--slide-size-lg)] group min-w-0 shrink-0 grow-0 basis-[var(--slide-size)]"
        >
            <div
                class="@2xs/item:flex-nowrap flex flex-wrap items-center gap-x-4 gap-y-3"
            >
                @if ($feature->image)
                    @php
                        $width = 200;
                        $height = floor($width * ($feature->image->height / $feature->image->width));
                    @endphp
                @endif

                @if ($feature->translation)
                    <div
                        class="prose prose-sm dark:prose-invert grid h-full grow [&>:first-child]:mt-0 [&>:last-child]:mb-0"
                    >
                        @if ($feature->translation->title)
                            <p class="text-md @2xs/item:text-lg mb-1 leading-6">
                                @if ($url && empty($feature->meta['link_text']))
                                    <a
                                        href="{{ $url }}"
                                        wire:navigate
                                        class="text-link hover:text-primary-600 focus:text-primary-600 font-medium no-underline"
                                    >
                                        <strong class="font-semibold">
                                            {{ $feature->translation->title }}
                                        </strong>
                                    </a>
                                @else
                                    <strong class="font-semibold">
                                        {{ $feature->translation->title }}
                                    </strong>
                                @endif
                            </p>
                        @endif

                        <div
                            class="line-clamp-4 break-words font-medium leading-6 opacity-80"
                        >
                            {!! $feature->translation->summary !!}
                        </div>

                        @if (! empty($feature->meta['link_text']))
                            <a
                                class="text-link hover:text-primary focus:text-primary font-medium no-underline focus:underline"
                                href="{{ $url }}"
                                title="{{ $feature->translation->title }}"
                                wire:navigate
                            >
                                {{ $feature->meta['link_text'] }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>

<?php
