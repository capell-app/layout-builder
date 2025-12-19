<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;

    $page = Frontend::page();
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
            :presenter="$widget->type->meta['content_presenter'] ?? null"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
        >
            <div>
                <x-capell::page.title
                    :$containerKey
                    :heading-size="$headingSize"
                    :title="$page->translation->title"
                />

                @if ($withDate)
                    <x-capell::page.published_date
                        class="mt-4 whitespace-nowrap"
                        :date="$page->publish_from ?: $page->created_at"
                    />
                @endif
            </div>
        </x-capell::content>
    </div>

    @if (($withAuthor && $author) || $tags->isNotEmpty())
        <div class="mb-4 flex items-end justify-between">
            @if ($withAuthor && $author)
                <x-capell::page.author :$author />
            @endif

            @if ($tags->isNotEmpty())
                <div
                    class="flex flex-col items-center gap-x-10 gap-y-6 md:flex-row md:justify-between lg:flex-row-reverse"
                >
                    <x-capell-blog::page.tags
                        :tagPage="$tagPage"
                        :tags="$tags"
                        with_tag_icon="true"
                    />
                </div>
            @endif
        </div>
    @endif

    @if ($withNextPrev && ($previousPage || $nextPage))
        <div
            class="mt-10 flex divide-y divide-gray-100 border-t border-gray-100 pt-6 md:divide-x md:divide-y-0"
        >
            @if ($previousPage)
                <x-capell::page.neighbor-link
                    :neighbor-page="$previousPage"
                    neighbor="previous"
                />
            @endif

            @if ($nextPage)
                <x-capell::page.neighbor-link
                    :neighbor-page="$nextPage"
                    neighbor="next"
                    class="ml-auto"
                />
            @endif
        </div>
    @endif
</x-capell-layout::widget.wrapper>

<?php
