<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;

                $language = Frontend::language();
                $site = Frontend::site();
                $page = Frontend::page();
@endphp

@props([
'container',
'containerKey',
'containerWidth' => null,
'showPageContent' => $widgetData['meta']['show_page_content'] ?? false,
'showPageTitle' => $widgetData['meta']['show_page_title'] ?? false,
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
    @if (($widget->translation && ($widget->translation->title || $widget->translation->content))
         || ($showPageContent && $page->translation->title)
         || ($showPageTitle && $page->translation->content))
        <x-capell::content
            class="mb-4"
            :compact="true"
            :content="$widget->translation->content ?? ($showPageContent ? $page->translation->content : null)"
            :presenter="$widget->type->meta['content_presenter'] ?? null"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
            :title="$widget->translation->title ?? ($showPageTitle ? $page->translation->title : null)"
        />
    @endif

    @if ($tags->isEmpty())
        <x-capell::no-results>
            {{ __('capell-blog::generic.no_tags_found') }}
        </x-capell::no-results>
    @else
        <ul class="@sm:grid-cols-2 @md:grid-cols-3 grid gap-x-6 gap-y-2">
            @foreach ($tags as $tag)
                @php($url = $tag->getPageUrl($tagPage, $language))
                <li>
                    <x-capell::badge
                        :$url
                        :count="$tag->pages_count"
                    >
                        {{ $tag->getTranslation('name', $language->code) }}
                    </x-capell::badge>
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
</x-capell-layout::widget.wrapper>

<?php
