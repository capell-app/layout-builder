{{--
    SaaS theme base layout.
    Slots:
    - $title   : page title (falls back to config app name)
    - $header  : optional replacement for the default <x-saas::header />
    - $footer  : optional replacement for the default <x-saas::saas-footer />
    - $head    : extra <head> content (e.g. meta/structured data)
    - $slot    : main content
--}}
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-theme="{{ $theme ?? 'light' }}"
>
    <head>
        <meta charset="utf-8" />
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        />
        <meta
            name="color-scheme"
            content="light dark"
        />
        <title>{{ $title ?? config('app.name', 'Capell') }}</title>
        @isset($head)
            {{ $head }}
        @endisset
    </head>
    <body
        class="min-h-screen bg-[var(--color-bg)] text-[var(--color-fg)] antialiased"
        data-spacing="{{ $spacing ?? 'balanced' }}"
    >
        <a
            href="#main"
            class="skip-to-content"
        >
            Skip to content
        </a>

        @isset($header)
            {{ $header }}
        @else
            <x-saas::header />
        @endisset

        <main
            id="main"
            role="main"
            tabindex="-1"
            class="focus:outline-none"
        >
            {{ $slot }}
        </main>

        @isset($footer)
            {{ $footer }}
        @else
            <x-saas::saas-footer />
        @endisset
    </body>
</html>
