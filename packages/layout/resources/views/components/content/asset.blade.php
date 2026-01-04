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

    $mergedClass = trim('content-asset ' . ($attributes->get('class') ?? ''));
@endphp
{{-- format-ignore-end --}}
<x-dynamic-component
    :component="$componentItem"
    :$loop
    :image="$image"
    :icon="$asset->meta['icon'] ?? null"
    :color="$asset->meta['color'] ?? null"
    :meta="$asset->meta"
    :link-text="$withLinkText ? ($asset->translation->meta['link_text'] ?? __('Read more')) : null"
    :summary="$withSummary && $asset->translation ? $asset->translation->summary : null"
    :title="$asset->translation?->label"
    :url="$withUrl && $asset->linkedPage ? $asset->linkedPage->pageUrl?->full_url : null"
    :class="$mergedClass"
/>

<?php
