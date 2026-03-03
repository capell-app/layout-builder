<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;

    $page = Frontend::page();
    $theme = Frontend::theme();
@endphp

@props([
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
    'headingSize' => $widget->meta['heading_size'] ?? 'h1',
    'withAuthor' => $widget->meta['with_author'] ?? false,
    'withDate' => $widget->meta['with_date'] ?? false,
    'withNextPrev' => $widget->meta['with_next_prev'] ?? false,
])
<x-capell-layout::widget.wrapper
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :margin="['t-md', 'b-lg']"
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
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
            :title="$page->translation->title"
            :heading-style="($widget->meta['heading_style'] ?? null) ?: $widget->type->meta['heading_style'] ?? null"
        >
            @if ($withDate)
                <div class="text-right">
                    <x-capell-blog::page.published-date
                        class="mt-4 whitespace-nowrap"
                        :date="$page->published_at ?: $page->publish_from ?: $page->created_at"
                    />
                </div>
            @endif
        </x-capell::content>
    </div>

    {!!
        app(\Capell\Frontend\Support\Render\RenderHookRegistry::class)->renderAll(
            \Capell\Frontend\Enums\RenderHookLocation::ArticleMeta,
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
