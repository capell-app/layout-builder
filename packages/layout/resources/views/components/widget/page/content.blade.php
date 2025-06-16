<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;
    use Capell\Layout\Actions\PageHasHeroWidgetAction;
@endphp

@props([
    'container',
    'containerKey',
    'headingTag' => $widget->meta['heading_tag'] ?? null,
    'headingSize' => $widget->meta['heading_size'] ?? 'h1',
    'loop',
    'pageRecord' => Frontend::getPage(),
    'pageContents' => $widget->meta['page_content'] ?? ['title', 'content', 'contents'],
    'size' => $widget->meta['size'] ?? 'lg',
    'widget',
    'widgetData',
])
@php
    $hasHero = PageHasHeroWidgetAction::run($pageRecord);

    $hasContent = collect(['content', 'contents', 'title'])
        ->contains(fn ($item): bool => in_array($item, $pageContents, true) && ! empty($pageRecord->translation->$item));

    if (! $headingTag) {
        $headingTag = ($hasHero ? 'h2' : 'h1');
    }
@endphp

@if ($hasContent)
    <x-capell::widget.wrapper
        :$container
        :$containerKey
        :index="$loop->index"
        :$widget
        :class="'widget-page-contents'.($loop->last ? ' mb-20' : ' mb-10')"
    >
        <x-capell::content
            :$containerKey
            :content="in_array('content', $pageContents, true) ? $pageRecord->translation->content : null"
            :contents="in_array('contents', $pageContents, true) ? $pageRecord->translation->contents : null"
            :heading-size="$headingSize"
            :heading-tag="$headingTag"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
            :title="in_array('title', $pageContents, true) && ! (empty($widgetData['meta']['hide_title']) && $hasHero) ? $pageRecord->translation->title : null"
            :title-component="$widgetData['title_component'] ?? 'capell::heading'"
            size="lg"
        />

        @if (! empty($widget->translation?->actions))
            <x-capell::actions
                class="mt-4"
                :actions="$widget->translation?->actions"
                button_color="primary"
            />
        @endif
    </x-capell::widget.wrapper>
@endif

<?php
