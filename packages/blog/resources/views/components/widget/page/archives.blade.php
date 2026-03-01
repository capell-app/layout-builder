<?php

declare(strict_types=1);

use Capell\Frontend\Facades\Frontend;

$site = Frontend::site();
$page = Frontend::page();
$theme = Frontend::theme();
$urlParams = Frontend::params();
?>

@props([
    'archiveDate' => $urlParams['archive_date'] ?? null,
    'archives' => [],
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'results',
    'showPageContent' => $widgetData['meta']['show_page_content'] ?? false,
    'showPageTitle' => $widgetData['meta']['show_page_title'] ?? false,
    'widget',
])
<x-capell-layout::widget.wrapper
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    @php
        $showTitle = empty($widgetData['hide_title'])
            && ($widget->translation?->title || ($showPageTitle && $page->translation->title));
        $showContent = empty($widgetData['hide_content'])
            && ($widget->translation?->content || ($showPageContent && $page->translation->content));
    @endphp

    @if ($showTitle || $showContent)
        <x-capell::content
            class="mb-6"
            :compact="true"
            :content="$showContent ? ($widget->translation->content ?: ($showPageContent ? $page->translation->content : null)) : null"
            :content-type="$widget->type->content_structure"
            :muted="in_array($containerKey, $theme->secondary_containers)"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
            :title="$showTitle ? ($widget->translation->title ?: ($showPageTitle ? $page->translation->title : null)) : null"
            :heading-style="($widget->meta['heading_style'] ?? null) ?: ($widget->type->meta['heading_style'] ?? null)"
            :heading-tag="$showPageTitle ? 'h1' : null"
        />
    @endif

    @if ($archives?->isEmpty())
        <x-capell::no-results>
            {!! isset($widget->translation->meta['no_results']) && $widget->translation->meta['no_results'] !== '' ? $widget->translation->meta['no_results'] : __('capell-blog::messages.no_archives_found') !!}
        </x-capell::no-results>
    @else
        <ul
            class="widget-archives-months @md:grid-cols-2 grid gap-x-6 divide-y divide-gray-100 dark:divide-gray-600"
        >
            @foreach ($archives as $archive)
                @php
                    $url = Capell\Blog\Actions\GenerateArchivePageUrl::run($archivePage->pageUrl, $archive);
                    $active = $archiveDate && $archiveDate->month === $archive->month && $archiveDate->year === $archive->year;
                @endphp

                <x-capell::list.list-item
                    :$url
                    :count="$archive->total"
                    :active="$active"
                    size="sm"
                    class="widget-archives-month px-2"
                >
                    {{ Carbon\Carbon::create()->day(1)->month($archive->month)->year($archive->year)->format('F Y') }}
                </x-capell::list.list-item>
            @endforeach
        </ul>
    @endif
</x-capell-layout::widget.wrapper>

<?php
