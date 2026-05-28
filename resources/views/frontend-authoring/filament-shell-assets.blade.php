@php
    $hasFilamentPanel = false;

    try {
        filament()->getCurrentOrDefaultPanel();
        $hasFilamentPanel = true;
    } catch (Throwable) {
        $hasFilamentPanel = false;
    }
@endphp

<style>
    [x-cloak=''],
    [x-cloak='x-cloak'],
    [x-cloak='1'] {
        display: none !important;
    }

    [x-cloak='inline-flex'] {
        display: inline-flex !important;
    }

    @media (max-width: 1023px) {
        [x-cloak='-lg'] {
            display: none !important;
        }
    }

    @media (min-width: 1024px) {
        [x-cloak='lg'] {
            display: none !important;
        }
    }
</style>

@filamentStyles

@if ($hasFilamentPanel)
    {{ filament()->getTheme()->getHtml() }}
    {{ filament()->getFontPreloadHtml() }}
    {{ filament()->getMonoFontPreloadHtml() }}
    {{ filament()->getSerifFontPreloadHtml() }}
    {{ filament()->getFontHtml() }}
    {{ filament()->getMonoFontHtml() }}
    {{ filament()->getSerifFontHtml() }}

    <style>
        :root {
            --font-family: '{!! filament()->getFontFamily() !!}';
            --mono-font-family: '{!! filament()->getMonoFontFamily() !!}';
            --serif-font-family: '{!! filament()->getSerifFontFamily() !!}';
            --sidebar-width: {{ filament()->getSidebarWidth() }};
            --collapsed-sidebar-width: {{ filament()->getCollapsedSidebarWidth() }};
            --default-theme-mode: {{ filament()->getDefaultThemeMode()->value }};
        }

        html.fi {
            --livewire-progress-bar-color: var(--primary-500);
        }
    </style>

    @if (! filament()->hasDarkMode())
        <script>
            localStorage.setItem('theme', 'light')
        </script>
    @elseif (filament()->hasDarkModeForced())
        <script>
            localStorage.setItem('theme', 'dark')
        </script>
    @else
        <script>
            const loadCapellLayoutBuilderAuthoringDarkMode = () => {
                window.theme = localStorage.getItem('theme') ?? @js(filament()->getDefaultThemeMode()->value)

                if (
                    window.theme === 'dark' ||
                    (window.theme === 'system' &&
                        window.matchMedia('(prefers-color-scheme: dark)')
                            .matches)
                ) {
                    document.documentElement.classList.add('dark')
                }
            }

            loadCapellLayoutBuilderAuthoringDarkMode()

            document.addEventListener(
                'livewire:navigated',
                loadCapellLayoutBuilderAuthoringDarkMode,
            )
        </script>
    @endif
@endif
