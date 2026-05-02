@props(['sitemapPage', 'level'])
<li class="sitemap-page-item">
    <a
        class="hover:bg-primary/80 focus:bg-primary bg-gray-100 text-sm hover:text-white focus:text-white"
        href="{{ $sitemapPage->url }}"
        title="{!! htmlspecialchars($sitemapPage->label) !!}"
        wire:navigate
    >
        {{ $sitemapPage->label }}
        @if ($sitemapPage->children?->isNotEmpty())
            <span class="sitemap-page-count text-xs font-medium">
                ({{ count($sitemapPage->children) }})
            </span>
        @endif
    </a>
    @if ($sitemapPage->children?->isNotEmpty())
        <ul>
            @foreach ($sitemapPage->children as $child_page)
                @include('capell::sitemap.sitemap-page', [
                    'sitemapPage' => $child_page,
                    'level' => $level + 1,
                ])
            @endforeach
        </ul>
    @endif
</li>
