{{--
  Modern CTA (Call-to-Action) Section Widget

  Props:
    - heading (string): Main heading
    - subheading (string): Secondary text
    - primaryButton (array): { label, url, icon? }
    - secondaryButton (array): { label, url }
    - layout (string): 'centered|split' - Default: 'centered'
    - accentColor (string): 'primary|secondary|tertiary' - Default: 'tertiary'
    - backgroundGradient (string): Custom gradient CSS
    - customizable (bool): Show admin hints
--}}

@props([
    'heading' => 'Ready to Create Stunning Layouts?',
    'subheading' => 'No coding required. Drag, drop, customize, and publish.',
    'primaryButton' => ['label' => 'Start Building', 'url' => '#', 'icon' => '🚀'],
    'secondaryButton' => ['label' => 'View Docs', 'url' => '#'],
    'layout' => 'centered',
    'accentColor' => 'tertiary',
    'backgroundGradient' => 'linear-gradient(135deg, #7c3aed 0%, #3131c0 100%)',
    'customizable' => true,
])

@php
    $accentColorMap = [
        'primary' => 'var(--mosaic-primary)',
        'secondary' => 'var(--mosaic-secondary)',
        'tertiary' => 'var(--mosaic-tertiary)',
    ];

    $accentColor = $accentColorMap[$accentColor] ?? $accentColorMap['tertiary'];
@endphp

<section
    class="mosaic-cta-section relative overflow-hidden rounded-lg py-16 md:py-20"
    style="background: {{ $backgroundGradient }};"
>
    {{-- Decorative Background Elements --}}
    <div class="absolute -top-40 -right-40 w-80 h-80 rounded-full opacity-10" style="background: rgba(255, 183, 132, 0.3);"></div>
    <div class="absolute -bottom-20 -left-20 w-60 h-60 rounded-full opacity-10" style="background: rgba(210, 187, 255, 0.3);"></div>

    {{-- Content --}}
    <div class="relative px-6 md:px-12">
        <div
            @class([
                'max-w-3xl',
                'centered' => 'mx-auto text-center',
                'split' => 'flex items-center justify-between gap-8',
            ])
        >
            {{-- Text Content --}}
            <div class="{{ $layout === 'split' ? 'flex-1' : '' }}">
                @if($heading)
                    <h2
                        class="text-3xl md:text-4xl lg:text-5xl font-bold mb-4 tracking-tight"
                        style="
                            color: var(--mosaic-on-surface);
                            font-family: var(--mosaic-font-headline);
                            letter-spacing: -0.02em;
                        "
                    >
                        {{ $heading }}
                    </h2>
                @endif

                @if($subheading)
                    <p
                        class="text-lg md:text-xl mb-8 leading-relaxed"
                        style="color: var(--mosaic-on-surface-variant);"
                    >
                        {{ $subheading }}
                    </p>
                @endif

                {{-- Buttons --}}
                @if($primaryButton || $secondaryButton)
                    <div class="flex gap-4 flex-wrap {{ $layout === 'centered' ? 'justify-center' : '' }}">
                        @if($primaryButton)
                            <a
                                href="{{ $primaryButton['url'] }}"
                                class="mosaic-btn mosaic-btn-primary inline-flex items-center gap-2"
                            >
                                @if(isset($primaryButton['icon']))
                                    <span class="text-lg">{{ $primaryButton['icon'] }}</span>
                                @endif
                                <span>{{ $primaryButton['label'] }}</span>
                            </a>
                        @endif

                        @if($secondaryButton)
                            <a
                                href="{{ $secondaryButton['url'] }}"
                                class="mosaic-btn mosaic-btn-secondary"
                                style="
                                    background-color: rgba(19, 19, 24, 0.7);
                                    border-color: var(--mosaic-on-surface);
                                    color: var(--mosaic-on-surface);
                                    backdrop-filter: blur(8px);
                                "
                            >
                                {{ $secondaryButton['label'] }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Illustration / Image (for split layout) --}}
            @if($layout === 'split')
                <div class="flex-1 text-center">
                    <div
                        class="inline-flex items-center justify-center w-40 h-40 rounded-2xl"
                        style="
                            background: rgba(255, 255, 255, 0.1);
                            backdrop-filter: blur(12px);
                            border: 1px solid rgba(255, 255, 255, 0.2);
                        "
                    >
                        <span class="text-6xl">✨</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Admin Hint --}}
    @if($customizable && auth()->check())
        <div class="relative mt-8 text-center">
            <span class="mosaic-text-label text-xs" style="opacity: 0.7; color: var(--mosaic-on-surface);">
                ✨ Customize: Heading, buttons, layout, and background gradient
            </span>
        </div>
    @endif
</section>

<style scoped>
    .text-3xl { font-size: 1.875rem; }
    .text-4xl { font-size: 2.25rem; }
    .text-5xl { font-size: 3rem; }
    .text-lg { font-size: 1.125rem; }
    .text-xl { font-size: 1.25rem; }
    .text-6xl { font-size: 3.75rem; }
    .text-xs { font-size: 0.75rem; }

    .font-bold { font-weight: 700; }

    .mb-4 { margin-bottom: 1rem; }
    .mb-8 { margin-bottom: 2rem; }
    .mt-8 { margin-top: 2rem; }
    .gap-4 { gap: 1rem; }
    .gap-8 { gap: 2rem; }

    .py-16 { padding-top: 4rem; padding-bottom: 4rem; }
    .py-20 { padding-top: 5rem; padding-bottom: 5rem; }
    .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
    .px-12 { padding-left: 3rem; padding-right: 3rem; }

    .relative { position: relative; }
    .absolute { position: absolute; }
    .overflow-hidden { overflow: hidden; }

    .max-w-3xl { max-width: 48rem; }
    .mx-auto { margin-left: auto; margin-right: auto; }

    .text-center { text-align: center; }

    .flex { display: flex; }
    .inline-flex { display: inline-flex; }
    .flex-wrap { flex-wrap: wrap; }
    .items-center { align-items: center; }
    .justify-center { justify-content: center; }
    .justify-between { justify-content: space-between; }

    .flex-1 { flex: 1; }

    .w-80 { width: 20rem; }
    .h-80 { height: 20rem; }
    .w-60 { width: 15rem; }
    .h-60 { height: 15rem; }
    .w-40 { width: 10rem; }
    .h-40 { height: 10rem; }

    .rounded-full { border-radius: 9999px; }
    .rounded-lg { border-radius: var(--mosaic-radius-lg); }
    .rounded-2xl { border-radius: 1rem; }

    .opacity-10 { opacity: 0.1; }
    .opacity-70 { opacity: 0.7; }

    .-top-40 { top: -10rem; }
    .-right-40 { right: -10rem; }
    .-bottom-20 { bottom: -5rem; }
    .-left-20 { left: -5rem; }

    .leading-relaxed { line-height: 1.625; }
    .tracking-tight { letter-spacing: -0.015em; }

    @media (max-width: 768px) {
        .md\:text-4xl { font-size: 2.25rem; }
        .md\:text-xl { font-size: 1.25rem; }
        .md\:py-20 { padding-top: 5rem; padding-bottom: 5rem; }
        .md\:px-12 { padding-left: 3rem; padding-right: 3rem; }

        .flex-wrap { flex-wrap: wrap; }
    }

    @media (min-width: 1024px) {
        .lg\:text-5xl { font-size: 3rem; }
    }
</style>
