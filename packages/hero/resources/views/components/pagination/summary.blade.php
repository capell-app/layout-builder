<?php

declare(strict_types=1);

?>

@props([
    'results' => '',
    'resultsFoundText' => __('capell-frontend::messages.results_found'),
])

@php
    if (! $results || (method_exists($results, 'total') ? $results->total() === 0 : $results->isEmpty())) {
        return;
    }

    $isPaginated = $results->perPage() < $results->total();
@endphp

<div
    {{
        $attributes
            ->class('pagination-info tracking-loose text-sm font-normal leading-none text-gray-500 dark:text-gray-400')
    }}
>
    @if ($isPaginated)
        {{ __('capell-frontend::messages.page') }}
        <span
            class="pagination-page font-semibold tracking-normal dark:text-white"
        >
            {{ $results->currentPage() }}
        </span>
        {{ __('capell-frontend::messages.of') }}
        <span class="font-semibold tracking-normal dark:text-white">
            {{ $results->lastPage() }}
        </span>
        &mdash;
    @endif

    <span
        class="pagination-total font-semibold tracking-normal dark:text-white"
    >
        {{ $results->total() }}
    </span>
    {{ $resultsFoundText }}
</div>

<?php
