<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="fi"
>
    <head>
        <meta charset="utf-8" />
        <meta
            name="csrf-token"
            content="{{ csrf_token() }}"
        />
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        />
        <title>{{ $title }}</title>
        @include('capell-layout-builder::frontend-authoring.filament-shell-assets')
        @livewireStyles
        <style>
            html {
                background: #f8fafc;
            }

            body.capell-layout-builder-authoring {
                margin: 0;
                min-height: 100vh;
                background: #f8fafc;
                color: #0f172a;
                font-family:
                    ui-sans-serif,
                    system-ui,
                    -apple-system,
                    BlinkMacSystemFont,
                    'Segoe UI',
                    sans-serif;
            }

            .capell-layout-builder-authoring__inner {
                box-sizing: border-box;
                min-height: 100vh;
                padding: 20px;
            }

            .capell-layout-builder-authoring__header {
                margin-bottom: 16px;
            }

            .capell-layout-builder-authoring__eyebrow {
                color: #64748b;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.06em;
                line-height: 1;
                margin-bottom: 7px;
                text-transform: uppercase;
            }

            .capell-layout-builder-authoring__title {
                color: #111827;
                font-size: 20px;
                font-weight: 800;
                line-height: 1.2;
                margin: 0;
            }

            .capell-layout-builder-authoring__description {
                color: #475569;
                font-size: 13px;
                line-height: 1.5;
                margin: 8px 0 0;
            }
        </style>
    </head>
    <body class="fi-body capell-layout-builder-authoring antialiased">
        <main class="capell-layout-builder-authoring__inner">
            <header class="capell-layout-builder-authoring__header">
                <div class="capell-layout-builder-authoring__eyebrow">
                    {{ __('capell-frontend-authoring::authoring.admin_editing') }}
                </div>
                <h1 class="capell-layout-builder-authoring__title">
                    {{ $title }}
                </h1>
                @if (! empty($description))
                    <p class="capell-layout-builder-authoring__description">
                        {{ $description }}
                    </p>
                @endif
            </header>

            @livewire('capell-layout-builder::filament.layout-builder', [
                'layoutId' => $layoutId,
                'siteId' => $siteId,
                'pageId' => $pageId,
                'pageClass' => $pageClass,
                'initialContainerKey' => $initialContainerKey,
                'initialWidgetIndex' => $initialWidgetIndex,
            ])
        </main>

        <script>
            document.addEventListener('livewire:init', function () {
                window.parent.postMessage(
                    {
                        type: 'capell-authoring:editor-loaded',
                    },
                    window.location.origin,
                )

                Livewire.on(
                    'capell-layout-builder-authoring-dirty',
                    function () {
                        window.parent.postMessage(
                            {
                                type: 'capell-authoring:dirty',
                            },
                            window.location.origin,
                        )
                    },
                )

                Livewire.on(
                    'capell-layout-builder-authoring-saved',
                    function (detail) {
                        window.parent.postMessage(
                            {
                                type: 'capell-authoring:saved',
                                detail: Array.isArray(detail)
                                    ? detail[0]
                                    : detail,
                            },
                            window.location.origin,
                        )
                    },
                )
            })
        </script>
        @filamentScripts(withCore: true)
        @livewireScripts
    </body>
</html>
