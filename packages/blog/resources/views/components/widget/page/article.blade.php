<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Enums\RenderHookLocation;
    use Capell\Frontend\Facades\Frontend;
    use Capell\Frontend\Support\Render\RenderHookRegistry;

    $page = Frontend::page();
    $theme = Frontend::theme();
@endphp

@props([
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
    'headingSize' => $widget->getMeta('heading_size', 'h1'),
    'withAuthor' => (bool) $widget->getMeta('with_author'),
    'withDate' => (bool) $widget->getMeta('with_date'),
    'withNextPrev' => (bool) $widget->getMeta('with_next_prev'),
])
<x-capell-layout::widget.wrapper
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
    container-class="flex flex-col gap-6"
>
    <div class="grid">
        <x-capell::content
            :$containerKey
            :image="$page->image"
            :heading-size="$headingSize"
            :content="$page->translation->content"
            :content-type="$page->type->content_structure"
            :muted="in_array($containerKey, $theme->secondary_containers)"
            :text-align="$widget->getMeta('align')"
            :title="$page->translation->title"
            :heading-style="$widget->getMeta('heading_style')"
        >
            @if ($withDate)
                <x-capell-blog::page.published-date
                    class="mt-4 whitespace-nowrap"
                    :date="$page->published_at ?: $page->visible_from ?: $page->created_at"
                />
            @endif
        </x-capell::content>
    </div>

    {!!
        app(RenderHookRegistry::class)->renderAll(
            RenderHookLocation::ArticleMeta,
            [
                'withAuthor' => $withAuthor,
                'author' => $author,
            ],
        )
    !!}

    @if ($withNextPrev && ($previousPage || $nextPage))
        <div
            class="neighbor-links mt-10 flex divide-y divide-gray-100 border-t border-gray-100 pt-6 md:divide-x md:divide-y-0"
        >
            @if ($previousPage)
                <x-capell-blog::page.neighbor-link
                    :neighbor-page="$previousPage"
                    neighbor="previous"
                />
            @endif

            @if ($nextPage)
                <x-capell-blog::page.neighbor-link
                    :neighbor-page="$nextPage"
                    neighbor="next"
                    class="ml-auto"
                />
            @endif
        </div>
    @endif
</x-capell-layout::widget.wrapper>

<?php
