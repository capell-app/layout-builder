<?php
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\SiteLoader;

$language = Frontend::language();
$site = Frontend::site();

$relatedSites = SiteLoader::related($site, $language);
if ($relatedSites->isEmpty()) {
    return;
}
?>

<div {{ $attributes->class(['space-y-4']) }}>
    <div
        class="grid items-center gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-4"
        role="menu"
    >
        @foreach ($relatedSites as $relatedSite)
            <a
                class="flex items-center gap-x-2 gap-y-1 text-[var(--color-footer)] lg:grid"
                href="{{ $relatedSite->siteDomain->full_url }}"
                role="menuitem"
                tabindex="-1"
                @wireNavigate
            >
                <span
                    class="text-link text-lg font-bold"
                    style="{{ $relatedSite->getThemeColor('primary') ? 'color:' . $relatedSite->getThemeColor('primary') : '' }}"
                >
                    {{ $relatedSite->translation->title }}
                </span>
                @if ($description = $relatedSite->translation->getMeta('description'))
                    <span class="text-sm leading-tight">
                        {{ $description }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>
</div>
