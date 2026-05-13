@props([
    'title' => '',
    'keywords' => '',
    'description' => '',
])

@php
    use Capell\Frontend\Facades\Frontend;

    $runtimeManifest = Frontend::getFrontendData('runtimeManifest');
    $usesLivewire = $runtimeManifest?->usesLivewire ?? false;
@endphp

<x-capell::app.head.tokens />

@if ($usesLivewire)
    <script>
        ;(function () {
            function setupTheme() {
                const isDarkMode =
                    localStorage.theme === 'dark' ||
                    (!localStorage.theme &&
                        window.matchMedia('(prefers-color-scheme: dark)')
                            .matches)

                document.documentElement.classList.toggle('dark', isDarkMode)
            }

            function updateHeaderSticky() {
                document.body.classList.toggle(
                    'header-sticky',
                    window.scrollY > 0,
                )
            }

            function handleHeaderAndTheme() {
                setupTheme()

                const header = document.getElementById('header')
                if (!header) return
                updateHeaderSticky()
            }

            setupTheme()

            window.removeEventListener('scroll', updateHeaderSticky)
            window.addEventListener('scroll', updateHeaderSticky)

            document.addEventListener('livewire:load', updateHeaderSticky)
            document.addEventListener(
                'livewire:navigated',
                handleHeaderAndTheme,
            )
        })()
    </script>
@endif
