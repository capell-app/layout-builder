{{--
  Modern Hero Banner Widget

  Props:
    - title (string): Hero heading
    - subtitle (string): Subheading/description
    - primaryCta (array): { label, url, icon?, color? }
    - secondaryCta (array): { label, url }
    - backgroundImage (string): Image URL
    - backgroundGradient (string): CSS gradient override
    - height (string): 'sm|md|lg|xl' - Default: 'lg'
    - textAlign (string): 'left|center|right' - Default: 'center'
    - accentColor (string): 'primary|secondary|tertiary' - Default: 'tertiary' (gold)
    - customizable (bool): Show admin customize button
--}}

@props([
    'title' => 'Welcome to Capell',
    'subtitle' => 'Create beautiful layouts without code',
    'primaryCta' => ['label' => 'Get Started', 'url' => '#'],
    'secondaryCta' => null,
    'backgroundImage' => null,
    'backgroundGradient' => 'linear-gradient(135deg, #7c3aed 0%, #3131c0 100%)',
    'height' => 'lg',
    'textAlign' => 'center',
    'accentColor' => 'tertiary',
    'customizable' => true,
])

@php
    $heightClasses = [
        'sm' => 'min-h-[300px]',
        'md' => 'min-h-[400px]',
        'lg' => 'min-h-[500px]',
        'xl' => 'min-h-[600px]',
    ];

    $textAlignClasses = [
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right',
    ];

    $heightClass = $heightClasses[$height] ?? $heightClasses['lg'];
    $textClass = $textAlignClasses[$textAlign] ?? $textAlignClasses['center'];
@endphp

<section
    @class([
        'mosaic-hero-banner relative overflow-hidden rounded-lg',
        $heightClass,
        $textClass,
    ])
    style="
        background-image: {{ $backgroundImage ? "url('$backgroundImage')" : 'none' }};
        background-size: cover;
        background-position: center;
        background-color: var(--mosaic-surface-container-high);
    "
>
    {{-- Background Overlay Gradient --}}
    <div
        class="absolute inset-0 opacity-90"
        style="background: {{ $backgroundGradient }};"
    ></div>

    {{-- Content --}}
    <div class="relative h-full flex flex-col justify-center items-{{ $textAlign }} px-6 py-12 md:px-12 md:py-16">
        <div class="max-w-2xl {{ $textAlign === 'center' ? 'mx-auto' : '' }}">
            {{-- Title --}}
            @if($title)
                <h1
                    class="text-4xl md:text-5xl font-bold tracking-tight mb-4"
                    style="
                        color: var(--mosaic-on-surface);
                        font-family: var(--mosaic-font-headline);
                        letter-spacing: -0.02em;
                    "
                >
                    {{ $title }}
                </h1>
            @endif

            {{-- Subtitle --}}
            @if($subtitle)
                <p
                    class="text-lg md:text-xl mb-8 leading-relaxed"
                    style="color: var(--mosaic-on-surface-variant);"
                >
                    {{ $subtitle }}
                </p>
            @endif

            {{-- CTA Buttons --}}
            @if($primaryCta || $secondaryCta)
                <div class="flex gap-4 justify-{{ $textAlign === 'center' ? 'center' : $textAlign }} flex-wrap">
                    @if($primaryCta)
                        <a
                            href="{{ $primaryCta['url'] }}"
                            class="mosaic-btn mosaic-btn-primary inline-flex items-center gap-2"
                        >
                            @if(isset($primaryCta['icon']))
                                <span>{{ $primaryCta['icon'] }}</span>
                            @endif
                            {{ $primaryCta['label'] }}
                        </a>
                    @endif

                    @if($secondaryCta)
                        <a
                            href="{{ $secondaryCta['url'] }}"
                            class="mosaic-btn mosaic-btn-secondary"
                        >
                            {{ $secondaryCta['label'] }}
                        </a>
                    @endif
                </div>
            @endif

            {{-- Admin Customization Badge --}}
            @if($customizable && auth()->check())
                <div class="mt-8 pt-8 border-t" style="border-color: var(--mosaic-outline-variant); opacity: 0.6;">
                    <span class="mosaic-text-label text-xs">
                        ✨ Customize: Title, Gradient, CTA buttons in properties panel
                    </span>
                </div>
            @endif
        </div>
    </div>
</section>

<style scoped>
    .text-left { text-align: left; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }

    .justify-left { justify-content: flex-start; }
    .justify-center { justify-content: center; }
    .justify-right { justify-content: flex-end; }

    .items-left { align-items: flex-start; }
    .items-center { align-items: center; }
    .items-right { align-items: flex-end; }

    .mx-auto { margin-left: auto; margin-right: auto; }
    .mb-4 { margin-bottom: 1rem; }
    .mb-8 { margin-bottom: 2rem; }
    .gap-4 { gap: 1rem; }
    .gap-2 { gap: 0.5rem; }
    .mt-8 { margin-top: 2rem; }
    .pt-8 { padding-top: 2rem; }

    .flex { display: flex; }
    .inline-flex { display: inline-flex; }
    .flex-col { flex-direction: column; }
    .flex-wrap { flex-wrap: wrap; }

    .justify-center { justify-content: center; }
    .justify-left { justify-content: flex-start; }
    .justify-right { justify-content: flex-end; }

    .items-center { align-items: center; }
    .items-left { align-items: flex-start; }
    .items-right { align-items: flex-end; }

    .relative { position: relative; }
    .absolute { position: absolute; }
    .inset-0 { top: 0; right: 0; bottom: 0; left: 0; }
    .h-full { height: 100%; }
    .overflow-hidden { overflow: hidden; }
    .rounded-lg { border-radius: var(--mosaic-radius-lg); }

    .leading-relaxed { line-height: 1.625; }
    .tracking-tight { letter-spacing: -0.015em; }
    .font-bold { font-weight: 700; }

    .text-4xl { font-size: 2.25rem; }
    .text-5xl { font-size: 3rem; }
    .text-lg { font-size: 1.125rem; }
    .text-xl { font-size: 1.25rem; }
    .text-xs { font-size: 0.75rem; }

    .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
    .py-12 { padding-top: 3rem; padding-bottom: 3rem; }
    .px-12 { padding-left: 3rem; padding-right: 3rem; }
    .py-16 { padding-top: 4rem; padding-bottom: 4rem; }

    @media (max-width: 768px) {
        .md\:text-5xl { font-size: 3rem; }
        .md\:text-xl { font-size: 1.25rem; }
        .md\:px-12 { padding-left: 3rem; padding-right: 3rem; }
        .md\:py-16 { padding-top: 4rem; padding-bottom: 4rem; }
    }
</style>
