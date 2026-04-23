@props([
    'site',
])
<div
    {{ $attributes->merge(['class' => 'footer-site-info space-y-4']) }}
>
    <a
        href="{{ $site->siteDomain->url }}"
        class="text-brand hover:text-primary focus:text-primary mb-3 inline-block"
    >
        @if ($site->logo || $site->logoInverted)
            @if ($site->logoInverted)
                <x-capell::logo
                    :media="$site->logoInverted"
                    :class="'footer-logo object-top-left max-h-[32vh] object-contain' . ($site->logo ? ' hidden dark:block' : '')"
                />
            @endif

            @if ($site->logo)
                <x-capell::logo
                    :media="$site->logo"
                    :class="'footer-logo object-top-left max-h-[32vh] object-contain' . ($site->logoInverted ? ' dark:hidden' : '')"
                />
            @endif
        @else
            <span class="footer-logo-text text-2xl font-semibold leading-tight">
                {!! $site->translation->title !!}
            </span>
        @endif
    </a>

    @if ($tagline = $site->translation->getMeta('tagline'))
        <p class="footer-tagline text-sm text-gray-300">
            {{ $tagline }}
        </p>
    @endif

    @if ($socialLinks = $site->getMeta('social_links'))
        <x-capell::footer.social-links :links="$socialLinks" />
    @endif
</div>
