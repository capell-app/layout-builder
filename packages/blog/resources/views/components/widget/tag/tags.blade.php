<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;

    $language = Frontend::language();
    $site = Frontend::site();
    $theme = Frontend::theme();
    $page = Frontend::page();
@endphp

@props([
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'showPageContent' => $widgetData['meta']['show_page_content'] ?? false,
    'showPageTitle' => $widgetData['meta']['show_page_title'] ?? false,
    'widget',
])
<x-capell-mosaic::widget.wrapper
    class="widget-tags"
    :$container
    :$containerKey
    :$containerWidth
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    @php
        $showTitle = $widget->getMeta("container_options.{$containerKey}.hide_title") !== true
            && ($widget->translation?->title || ($showPageTitle && $page->translation->title));
        $showContent = $widget->getMeta("container_options.{$containerKey}.hide_content") !== true
            && ($widget->translation?->content || ($showPageContent && $page->translation->content));
    @endphp

    @if ($showTitle || $showContent)
        <x-capell::content
            class="mb-6 mt-10"
            :compact="true"
            :content="$showContent ? ($widget->translation->content ?: ($showPageContent ? $page->translation->content : null)) : null"
            :content-type="$widget->translation->content ? $widget->type->content_structure : ($showPageContent ? $page->type->content_structure : null)"
            :divider="$widget->getMeta('content_divider')"
            :muted="in_array($containerKey, $theme->secondary_containers)"
            :text-align="$widget->getMeta('align')"
            :title="$showTitle ? ($widget->translation->title ?: ($showPageTitle ? $page->translation->title : null)) : null"
            :heading-style="$widget->getMeta('heading_style')"
            :heading-tag="$showPageTitle ? 'h1' : null"
        />
    @endif

    @if ($tags->isEmpty())
        <x-capell::no-results>
            {!! $widget->translation->getMeta('no_results', __('capell-blog::messages.no_tags_found')) !!}
        </x-capell::no-results>
    @else
        <ul class="flex flex-wrap gap-2">
            @foreach ($tags as $tag)
                @php($url = $tag->getUrl($tagPage, $language))
                <li>
                    <x-capell-blog::tag :$url>
                        {{ $tag->getTranslation('name', $language->code) }}
                        <x-slot:count>
                            ({{ $tag->taggables_count }})
                        </x-slot>
                    </x-capell-blog::tag>
                </li>
            @endforeach
        </ul>
    @endif
    @if (method_exists($tags, 'total') && $tags->hasPages())
        <x-capell::pagination
            :results="$tags"
            :scrollToElement="$containerKey . '-' . $widget->key . '-' . $loop->index"
        />
    @endif
</x-capell-mosaic::widget.wrapper>

<?php
