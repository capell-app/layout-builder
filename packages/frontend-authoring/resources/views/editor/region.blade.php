<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>
            {{ __('capell-frontend-authoring::authoring.edit_region') }}
        </title>
        @livewireStyles
    </head>
    <body>
        <livewire:capell-frontend-authoring::edit-region-field
            :payload="$payload"
        />

        <script>
            document.addEventListener('livewire:init', function () {
                Livewire.on('capell-authoring-saved', function () {
                    window.parent.postMessage(
                        { type: 'capell-authoring:saved' },
                        window.location.origin,
                    )
                })
            })
        </script>
        @livewireScripts
    </body>
</html>
