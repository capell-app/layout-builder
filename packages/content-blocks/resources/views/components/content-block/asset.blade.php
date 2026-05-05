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

    $configurator = strtolower((string) ($asset->type?->configurator ?? ''));
    $normalizedConfigurator = preg_replace('/[^a-z0-9]/', '', $configurator) ?? '';
    $contentBlockComponent = match (true) {
        str_contains($normalizedConfigurator, 'accordion') => 'capell-content-blocks::content-block.blocks.accordion',
        str_contains($normalizedConfigurator, 'calltoaction') => 'capell-content-blocks::content-block.blocks.call-to-action',
        str_contains($normalizedConfigurator, 'comparison') => 'capell-content-blocks::content-block.blocks.comparison',
        str_contains($normalizedConfigurator, 'counter') => 'capell-content-blocks::content-block.blocks.counter',
        str_contains($normalizedConfigurator, 'divider') => 'capell-content-blocks::content-block.blocks.divider',
        str_contains($normalizedConfigurator, 'faq') => 'capell-content-blocks::content-block.blocks.faq',
        str_contains($normalizedConfigurator, 'features') => 'capell-content-blocks::content-block.blocks.features',
        str_contains($normalizedConfigurator, 'logos') => 'capell-content-blocks::content-block.blocks.logos',
        str_contains($normalizedConfigurator, 'pricing') => 'capell-content-blocks::content-block.blocks.pricing',
        str_contains($normalizedConfigurator, 'stats') => 'capell-content-blocks::content-block.blocks.stats',
        str_contains($normalizedConfigurator, 'table') => 'capell-content-blocks::content-block.blocks.table',
        str_contains($normalizedConfigurator, 'tabs') => 'capell-content-blocks::content-block.blocks.tabs',
        str_contains($normalizedConfigurator, 'team') => 'capell-content-blocks::content-block.blocks.team',
        str_contains($normalizedConfigurator, 'timeline') => 'capell-content-blocks::content-block.blocks.timeline',
        default => $componentItem,
    };
@endphp
{{-- format-ignore-end --}}
<x-dynamic-component
    :component="$contentBlockComponent"
    :$asset
    :$loop
    :$size
    :color="$asset->getMeta('color')"
    :icon="$asset->getMeta('icon')"
    :image="$image"
    :link-text="$withLinkText ? $asset->translation->getMeta('link_text', __('Read more')) : null"
    :meta="$asset->meta"
    :summary="$withSummary && $asset->translation ? $asset->translation->summary : null"
    :title="$asset->translation?->label"
    :url="$withUrl && $asset->linkedPage ? $asset->linkedPage->pageUrl?->full_url : null"
    :attributes="$attributes->merge(['class' => 'content_block-asset'])"
/>
