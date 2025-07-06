<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;

    $language = Frontend::getLanguage();
    $site = Frontend::getSite();
@endphp

@props([
    'container',
    'containerKey',
    'containerWidth' => null,
    'hideContent' => $widgetData['meta']['hide_content'] ?? false,
    'loop',
    'widget',
])
<x-capell-layout::widget.wrapper
    class="widget-tags"
    :$container
    :$containerKey
    :$containerWidth
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    @if ($widget->translation && ! $hideContent)
        <x-capell::content
            class="mb-4"
            :compact="true"
            :content="$widget->translation->content"
            :contents="$widget->translation->content ? null : $widget->translation->contents"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
            :title="$widget->translation->title"
        />
    @endif

    @if ($tags->isEmpty())
        <x-capell::no-results>
            {{ __('No tags found.') }}
        </x-capell::no-results>
    @else
        <ul class="divide-y divide-gray-100 dark:divide-gray-600">
            @foreach ($tags as $tag)
                @php($url = $tagPage->pageUrl->full_url.'/'.$tag->getTranslation('slug', $language->code))
                <x-capell::list.list-item
                    :$url
                    :count="$tag->pages_count"
                    size="sm"
                >
                    {{ $tag->getTranslation('name', $language->code) }}
                </x-capell::list.list-item>
            @endforeach
        </ul>
    @endif
    @if (method_exists($tags, 'total') && $tags->hasPages())
        <x-capell::pagination
            :results="$tags"
            :scrollToElement="$containerKey.'-'.$widget->key.'-'.$index"
        />
    @endif
</x-capell-layout::widget.wrapper>

<?php
