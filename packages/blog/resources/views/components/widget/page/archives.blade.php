<?php

declare(strict_types=1);

use Capell\Frontend\Facades\Frontend;

$site = Frontend::site();
$page = Frontend::page();
$pageParams = Frontend::params();
?>

@props([
'archives' => [],
'container',
'containerKey',
'containerWidth' => null,
'showPageContent' => $widgetData['meta']['show_page_content'] ?? false,
'showPageTitle' => $widgetData['meta']['show_page_title'] ?? false,
'loop',
'results',
'archiveDate' => $pageParams['archive_date'] ?? null,
'widget',
])
<x-capell-layout::widget.wrapper
    class="widget-archive"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    @if (($widget->translation && ($widget->translation->title || $widget->translation->content))
         || ($showPageContent && $page->translation->title)
         || ($showPageTitle && $page->translation->content))
        <x-capell::content
            class="mb-2"
            :compact="true"
            :content="$widget->translation->content ?? ($showPageContent ? $page->translation->content : null)"
            :presenter="$widget->type->meta['content_presenter'] ?? null"
            :title="$widget->translation->title ?? ($showPageTitle ? $page->translation->title : null)"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
        />
    @endif

    @if ($archives?->isEmpty())
        <x-capell::no-results>
            {{ __('capell-blog::generic.no_archives_found') }}
        </x-capell::no-results>
    @else
        <ul
            class="@md:grid-cols-2 grid gap-x-6 divide-y divide-gray-100 dark:divide-gray-600"
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
                    class="px-2"
                >
                    {{ Carbon\Carbon::create()->day(1)->month($archive->month)->year($archive->year)->format('F Y') }}
                </x-capell::list.list-item>
            @endforeach
        </ul>
    @endif
</x-capell-layout::widget.wrapper>

<?php
