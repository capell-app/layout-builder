<?php

declare(strict_types=1);

use Capell\Frontend\Facades\Frontend;

$page = Frontend::page();
?>

@props([
'container',
'containerKey',
'containerWidth' => null,
'headingTag' => $widget->meta['heading_tag'] ?? null,
'headingSize' => $widget->meta['heading_size'] ?? 'h1',
'loop',
'pageContents' => $widget->meta['page_content'] ?? ['title', 'content'],
'size' => $widget->meta['size'] ?? 'lg',
'widget',
'widgetData',
])
@php
    $hasPrimaryHeading = Frontend::getFrontendData('has_primary_heading');

            $hasContent = collect(['content', 'title'])
                ->contains(fn ($item): bool => in_array($item, $pageContents, true) && ! empty($page->translation->{$item}));

            if (! $headingTag) {
                $headingTag = ($hasPrimaryHeading ? 'h2' : 'h1');
            }
@endphp

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
            @if ($page->type->content_structure === ContentStructureEnum::Blocks)
                <x-capell::blocks
                    :blocks="$page->translation->content"
                    :layout="$layout"
                    :page="$page"
                />
            @else
                <x-capell::content
                    :content="$page->translation->content"
                    :heading-size="$headingSize"
                    :heading-tag="$headingTag"
                    :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
                    :title="in_array('title', $pageContents, true) && ! (empty($widgetData['meta']['show_page_title']) && $hasPrimaryHeading) ? $page->translation->title : null"
                />
            @endif
        @endif

        @if (! empty($widget->translation?->actions))
            <x-capell::actions
                class="mt-4"
                :actions="$widget->translation?->actions"
                button_color="primary"
            />
        @endif
    </x-capell-layout::widget.wrapper>
@endif

<?php
