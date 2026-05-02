@php
    use Capell\Core\Models\PageUrl;
    use Capell\Frontend\Facades\Frontend;
    use Capell\Frontend\Support\Loader\PageLoader;
    use Capell\Frontend\Support\Loader\SiteLoader;
    use Capell\Mosaic\Enums\ActionLinkEnum;

    $page = Frontend::page();
    $language = Frontend::language();
    $site = Frontend::site();
    $theme = Frontend::theme();
@endphp

@props([
    'align' => 'start',
    'actions' => '',
    'actionItemClass' => '',
    'color' => 'light',
    'buttonSize' => 'lg',
    'buttonWeight' => 'bold',
    'buttonOutline' => null,
    'buttonColor' => 'primary',
])
<div
    {{
        $attributes->class([
            'actions flex flex-wrap gap-2 lg:gap-x-4',
            'justify-center' => $align === 'center',
            'justify-start' => $align === 'start' || $align === 'left',
            'justify-end' => $align === 'end' || $align === 'right',
        ])
    }}
>
    {{ $slot }}
    @foreach ($actions as $action)
        {{-- format-ignore-start --}}
        @php
            $url = $action['url'] ?? '';
            $wireNavigation = false;

            $type = ActionLinkEnum::tryFrom($action['type'] ?? '');

            switch ($type) {
                case ActionLinkEnum::Link:
                    $url = $action['url'] ?? '';
                    break;
                case ActionLinkEnum::Page:
                    if (
                        blank($action['site_id'] ?? null)
                        || blank($action['pageable_type'] ?? null)
                        || blank($action['pageable_id'] ?? null)
                    ) {
                        continue 2;
                    }

                    $targetSite = (int) $action['site_id'] === (int) $site->id
                        ? $site
                        : SiteLoader::getSites()->firstWhere('id', $action['site_id']);

                    if (! $targetSite instanceof \Capell\Core\Models\Site) {
                        continue 2;
                    }

                    $pageUrl = PageLoader::getUrlById(
                        pageType: $action['pageable_type'],
                        pageId: (int) $action['pageable_id'],
                        site: $targetSite,
                        language: $language,
                    );

                    if (! $pageUrl instanceof PageUrl) {
                        continue 2;
                    }

                    $url = $pageUrl->full_url;
                    break;
            }

            throw_unless($url, InvalidArgumentException::class, 'Action URL is missing.');

            $label = $action['label'] ?? $pageUrl->translation->link_text ?? '';

            $wireNavigation = true;
        @endphp
        {{-- format-ignore-end --}}

        <x-capell::button
            :$url
            :target="$action['target'] ?? ''"
            :color="$action['color'] ?? $buttonColor"
            :color="$color"
            :icon="$action['icon'] ?? ''"
            :outline="$buttonOutline === false"
            :size="$buttonSize"
            :weight="$buttonWeight"
            :wire-navigation="$wireNavigation"
            :class="'action-item' . ' ' . ($actionItemClass ?? '')"
        >
            @if ($action['hide_label'] ?? false)
                <span class="sr-only">
                    {{ $label }}
                </span>
            @else
                {{ $label }}
            @endif
        </x-capell::button>
    @endforeach
</div>
