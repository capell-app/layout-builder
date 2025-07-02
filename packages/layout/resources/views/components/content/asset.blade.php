<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;
@endphp

@props([
    'asset',
    'componentItem',
    'language' => Frontend::getLanguage(),
    'withLinkText' => false,
    'withImage' => false,
    'withSummary' => false,
    'withTags' => false,
    'withUrl' => true,
    'loop',
])
<x-dynamic-component
    :component="$componentItem"
    :$loop
    :image="$withImage ? $asset->image : null"
    :link-text="$withLinkText ? ($asset->translation->meta['link_text'] ?? __('Read more')) : null"
    :summary="$withSummary && $asset->translation ? $asset->translation->summary : null"
    :tags="$withTags ? $asset->tags : null"
    :title="$asset->translation?->label"
    :url="$withUrl ? $asset->page->pageUrl?->full_url : null"
    class="content-asset"
/>

<?php
