<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;

    $language = Frontend::language();
@endphp

@props([
    'asset',
    'componentItem',
    'size' => null,
    'loop',
    'withImage' => false,
    'withLinkText' => false,
    'withSummary' => false,
    'withUrl' => true,
])
{{-- format-ignore-start --}}
@php
    $image = null;
    if ($withImage) {
        $image = $asset->relationLoaded('image') ? $asset->image : $asset->media->first();
    }
@endphp
{{-- format-ignore-end --}}
<x-dynamic-component
    :component="$componentItem"
    :$loop
    :$size
    :color="$asset->meta['color'] ?? null"
    :icon="$asset->meta['icon'] ?? null"
    :image="$image"
    :link-text="$withLinkText ? ($asset->translation->meta['link_text'] ?? __('Read more')) : null"
    :meta="$asset->meta"
    :summary="$withSummary && $asset->translation ? $asset->translation->summary : null"
    :title="$asset->translation?->label"
    :url="$withUrl && $asset->linkedPage ? $asset->linkedPage->pageUrl?->full_url : null"
    :attributes="$attributes->merge(['class' => 'content-asset'])"
/>

<?php
