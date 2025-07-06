<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;

    $site = Frontend::getSite();
    $pageParams = Frontend::getPageParams();
@endphp

@props([
    'archives' => [],
    'container',
    'containerKey',
    'containerWidth' => null,
    'hideContent' => $widgetData['meta']['hide_content'] ?? false,
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
    @if ($widget->translation && ! $hideContent)
        <x-capell::content
            class="mb-4"
            :compact="true"
            :content="$widget->translation->content"
            :contents="$widget->translation->content ? null : $widget->translation->contents"
            :title="$widget->translation->title"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
        />
    @endif

    @if ($archives?->isEmpty())
        <x-capell::no-results />
    @else
        <ul class="divide-y divide-gray-100 dark:divide-gray-600">
            @foreach ($archives as $archive)
                @php
                    $url = $archivePage->pageUrl->full_url.'/'.$archive->year.'-'.$archive->month;
                    $active = $archiveDate && $archiveDate->month === $archive->month && $archiveDate->year === $archive->year;
                @endphp

                <x-capell::list.list-item
                    :$url
                    :count="$archive->total"
                    :active="$active"
                    size="sm"
                >
                    {{ Carbon\Carbon::create()->day(1)->month($archive->month)->year($archive->year)->format('F Y') }}
                </x-capell::list.list-item>
            @endforeach
        </ul>
    @endif
</x-capell-layout::widget.wrapper>

<?php
