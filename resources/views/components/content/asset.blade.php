<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\FrontendLoader;

    $language = FrontendLoader::getLanguage();
@endphp

@props([
    'asset',
    'componentItem',
    'loop',
    'withImage' => false,
    'withLinkText' => false,
    'withSummary' => false,
    'withUrl' => true,
])

<x-dynamic-component
    :component="$componentItem"
    :$loop
    :image="$withImage ? $asset->image : null"
    :icon="$asset->meta['icon'] ?? null"
    :color="$asset->meta['color'] ?? null"
    :meta="$asset->meta"
    :link-text="$withLinkText ? ($asset->translation->meta['link_text'] ?? __('Read more')) : null"
    :summary="$withSummary && $asset->translation ? $asset->translation->summary : null"
    :title="$asset->translation?->label"
    :url="$withUrl && $asset->linkedPage ? $asset->linkedPage->pageUrl?->full_url : null"
    class="content-asset"
/>

<?php
