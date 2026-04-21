<?php

declare(strict_types=1);

?>

@props([
    'columns' => $widget->getMeta('columns', 3),
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

@php
    $gridClasses = [
        2 => 'grid-cols-1 md:grid-cols-2',
        3 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
        4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
    ];

    $gridClass = $gridClasses[(int) $columns] ?? $gridClasses[3];

    $socialIcons = [
        'twitter' => '𝕏',
        'linkedin' => 'in',
        'github' => '🐙',
        'website' => '🌐',
        'email' => '✉',
    ];
@endphp

<x-capell-mosaic::widget.wrapper
    class="widget-ap-team-members"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section class="px-6 py-12 md:px-12 md:py-16">
        @if ($widget->translation)
            <div class="mx-auto mb-12 max-w-2xl text-center">
                @if ($widget->translation->title)
                    <h2
                        class="mb-4 text-3xl font-bold tracking-tight text-gray-900 md:text-4xl"
                    >
                        {{ $widget->translation->title }}
                    </h2>
                @endif

                @if ($widget->translation->content)
                    <p class="text-lg text-gray-500">
                        {{ strip_tags($widget->translation->content) }}
                    </p>
                @endif
            </div>
        @endif

        <div class="{{ $gridClass }} grid gap-6">
            @forelse ($widget->assets as $widgetAsset)
                @php
                    $role = $widgetAsset->asset->getMeta('position');
                    $tags = $widgetAsset->asset->getMeta('tags', []);
                    $social = $widgetAsset->asset->getMeta('social', []);
                    $media = $widgetAsset->asset->media->first() ?? $widgetAsset->asset->image ?? null;
                    $icon = $widgetAsset->asset->getMeta('icon');
                @endphp

                <div
                    class="rounded-xl border border-stone-200 bg-white p-6 text-center"
                >
                    @if ($media)
                        <div
                            class="mx-auto mb-4 h-20 w-20 overflow-hidden rounded-full"
                        >
                            <img
                                src="{{ $media->getFullUrl() }}"
                                alt="{{ $widgetAsset->asset->translation?->title }}"
                                class="h-full w-full object-cover"
                            />
                        </div>
                    @elseif ($icon)
                        <div
                            class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-stone-100 text-5xl"
                        >
                            {{ $icon }}
                        </div>
                    @endif

                    @if ($widgetAsset->asset->translation?->title)
                        <h3
                            class="mb-1 text-lg font-bold tracking-tight text-gray-900"
                        >
                            {{ $widgetAsset->asset->translation->title }}
                        </h3>
                    @endif

                    @if ($role)
                        <p
                            class="mb-3 text-sm font-semibold uppercase tracking-wide text-emerald-700"
                        >
                            {{ $role }}
                        </p>
                    @endif

                    @if ($widgetAsset->asset->translation?->content)
                        <p class="mb-4 text-sm leading-relaxed text-gray-500">
                            {{ strip_tags($widgetAsset->asset->translation->content) }}
                        </p>
                    @endif

                    @if (count($tags) > 0)
                        <div class="mb-4 flex flex-wrap justify-center gap-2">
                            @foreach ($tags as $tag)
                                <span
                                    class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800"
                                >
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if (count($social) > 0)
                        <div
                            class="flex justify-center gap-3 border-t border-stone-100 pt-4"
                        >
                            @foreach ($social as $platform => $url)
                                @if ($url && isset($socialIcons[$platform]))
                                    <a
                                        href="{{ $url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        title="{{ ucfirst($platform) }}"
                                        class="font-semibold text-gray-400 transition-colors hover:text-emerald-700"
                                    >
                                        {{ $socialIcons[$platform] }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-span-full py-12 text-center">
                    <p class="text-gray-500">No team members configured</p>
                </div>
            @endforelse
        </div>
    </section>
</x-capell-mosaic::widget.wrapper>

<?php
