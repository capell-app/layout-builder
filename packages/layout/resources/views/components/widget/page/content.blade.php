<?php

declare(strict_types=1);

?>

@php
    use Capell\Core\Contracts\Pageable;
    use Capell\Core\Enums\ContentStructure;
    use Capell\Frontend\Facades\Frontend;

    $page = Frontend::page();
    $layout = Frontend::layout();
    $theme = Frontend::theme();
@endphp

@props([
    'container',
    'containerKey',
    'containerWidth' => null,
    'headingTag' => $widget->getMeta('heading_tag'),
    'headingSize' => $widget->getMeta('heading_size', 'h1'),
    'loop',
    'pageContents' => (array) $widget->getMeta('page_content', ['title', 'content']),
    'size' => $widget->getMeta('size', 'lg'),
    'widget',
    'widgetData',
])
{{-- format-ignore-start --}}
@php
    $hasPrimaryHeading = Frontend::getFrontendData('has_primary_heading');

    $hasContent = collect(['content', 'title'])
        ->contains(fn ($item): bool => in_array($item, $pageContents, true) && ! empty($page->translation->{$item}));

    if (! $headingTag) {
        $headingTag = ($hasPrimaryHeading ? 'h2' : 'h1');
    }
@endphp
{{-- format-ignore-end --}}
@if ($hasContent)
    <x-capell-layout::widget.wrapper
        :$container
        :$containerKey
        :$containerWidth
        :index="$loop->index"
        :$widget
        :class="'widget-page-contents' . ($loop->last ? ' mb-20' : ' mb-10')"
        tag="article"
    >
        @if (in_array('content', $pageContents, true))
            @if ($page->type->content_structure === ContentStructure::Blocks)
                <x-capell::blocks
                    :blocks="$page->translation->content"
                    :$layout
                    :$containerKey
                    :$page
                />
            @else
                <x-capell::content
                    :content="$page->translation->content"
                    :content-type="$page->type->content_structure"
                    :divider="$widget->getMeta('content_divider')"
                    :heading-size="$headingSize"
                    :heading-tag="$headingTag"
                    :muted="in_array($containerKey, $theme->secondary_containers)"
                    :image="$page->image"
                    :text-align="$widget->getMeta('align')"
                    :title="in_array('title', $pageContents, true) && ! (empty($this->widgetData['meta']['show_page_title']) && $hasPrimaryHeading) ? $page->translation->title : null"
                />
            @endif
        @endif

        @if (! empty($widget->translation?->actions))
            <x-capell-layout::actions
                class="mt-4"
                :actions="$widget->translation?->actions"
                button_color="primary"
            />
        @endif

        @if ($previousPage instanceof Pageable || $nextPage instanceof Pageable)
            <div class="clear-both">
                <div
                    class="neighbor-links mt-10 flex divide-y divide-gray-100 border-t border-gray-100 pt-6 md:divide-x md:divide-y-0"
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
            </div>
        @endif
    </x-capell-layout::widget.wrapper>
@endif

<?php
