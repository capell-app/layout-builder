@php
    use Capell\Frontend\Actions\GetLayoutContainerWidthAction;
    use Capell\Frontend\Facades\Frontend;

    $theme = Frontend::theme();
    $containerWidth = GetLayoutContainerWidthAction::run();
@endphp

<div class="flex min-h-screen flex-col bg-white dark:bg-gray-900">
    <a class="sr-only" href="#main">
        {{ __('capell-frontend::generic.skip_link') }}
    </a>

    @if (! isset($theme['meta']['header']) || $theme['meta']['header'] !== false)
        @if (! empty($theme['meta']['header_file']))
            <x-dynamic-component :component="$theme['meta']['header_file']" />
        @else
            <x-capell::header.index />
        @endif
    @endif

    <main
        id="main"
        @class([
            'capell-site-search-main relative z-0 flex min-h-full flex-1 flex-col overflow-x-hidden lg:!min-h-0',
            $theme['meta']['main_class'] ?? 'py-6 lg:py-10',
            $containerWidth->getContainerClass(),
        ])
    >
        @include('capell-site-search::pages.search', ['query' => $query, 'results' => $results])
    </main>

    @if (! isset($theme['meta']['footer']) || $theme['meta']['footer'] !== false)
        <x-dynamic-component
            :component="$theme['meta']['footer_file'] ?? 'capell::footer'"
        />
    @endif
</div>
