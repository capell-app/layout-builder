@php
    use Illuminate\Support\Str;
@endphp

{{-- Open Graph --}}
@if ($meta->url)
    <meta property="og:url" content="{{ $meta->url }}" />
@endif

<meta property="og:type" content="{{ $meta->ogType->value }}" />
<meta property="og:title" content="{{ $meta->title }}" />
@if ($meta->description)
    <meta property="og:description" content="{{ $meta->description }}" />
@endif

<meta property="og:locale" content="{{ $meta->locale }}" />
@foreach ($page->pageUrls as $pageUrl)
    @php
        $alternateDomain = $site->siteDomains->firstWhere('language_id', $pageUrl->language_id);
        if (! $alternateDomain || $alternateDomain->language_id === $language->id) {
            continue;
        }
    @endphp

    <meta
        property="og:locale:alternate"
        content="{{ Str::of($alternateDomain->language->locale)->lower()->replace('_', '-') }}"
    />
@endforeach

@if ($meta->siteName)
    <meta property="og:site_name" content="{{ $meta->siteName }}" />
@endif

@if ($meta->imageUrl)
    <meta property="og:image" content="{{ $meta->imageUrl }}" />
    @if ($meta->imageWidth)
        <meta property="og:image:width" content="{{ $meta->imageWidth }}" />
    @endif

    @if ($meta->imageHeight)
        <meta property="og:image:height" content="{{ $meta->imageHeight }}" />
    @endif

    @if ($meta->imageMimeType)
        <meta property="og:image:type" content="{{ $meta->imageMimeType }}" />
    @endif
@endif

{{-- Article-specific Open Graph --}}
@if ($meta->ogType->isArticle())
    @if ($meta->articlePublishedTime)
        <meta
            property="article:published_time"
            content="{{ $meta->articlePublishedTime }}"
        />
    @endif

    @if ($meta->articleModifiedTime)
        <meta
            property="article:modified_time"
            content="{{ $meta->articleModifiedTime }}"
        />
    @endif

    @if ($meta->articleAuthor)
        <meta property="article:author" content="{{ $meta->articleAuthor }}" />
    @endif
@endif

{{-- Twitter / X --}}
<meta name="twitter:card" content="{{ $meta->twitterCard }}" />
@if ($meta->twitterHandle)
    <meta name="twitter:site" content="{{ $meta->twitterHandle }}" />
    <meta name="twitter:creator" content="{{ $meta->twitterHandle }}" />
@endif

<meta name="twitter:title" content="{{ $meta->title }}" />
@if ($meta->description)
    <meta name="twitter:description" content="{{ $meta->description }}" />
@endif

@if ($meta->imageUrl)
    <meta name="twitter:image" content="{{ $meta->imageUrl }}" />
    @if ($meta->imageAlt)
        <meta name="twitter:image:alt" content="{{ $meta->imageAlt }}" />
    @endif
@endif
