{{--
  Modern Feature List Widget

  Props:
    - title (string): Section heading
    - description (string): Section description
    - features (array): Array of feature objects { icon, title, description }
    - layout (string): 'vertical|grid' - Default: 'grid'
    - columns (int): Number of columns (2,3,4) - Default: 3
    - customizable (bool): Show admin hints
--}}

@props([
    'title' => 'Powerful Features',
    'description' => 'Everything you need to create amazing layouts',
    'features' => [
        ['icon' => '⚡', 'title' => 'Lightning Fast', 'description' => 'Optimized for performance'],
        ['icon' => '🎨', 'title' => 'Fully Customizable', 'description' => 'Endless styling options'],
        ['icon' => '🔧', 'title' => 'Easy to Use', 'description' => 'No coding required'],
    ],
    'layout' => 'grid',
    'columns' => 3,
    'animation' => 'fade-in',
    'customizable' => true,
])

@php
    $gridClasses = [
        2 => 'grid-cols-1 md:grid-cols-2',
        3 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
        4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
    ];

    $gridClass = $gridClasses[$columns] ?? $gridClasses[3];
@endphp

<section class="mosaic-feature-list py-12 md:py-16 px-6 md:px-12">
    {{-- Header --}}
    @if($title || $description)
        <div class="mb-12 max-w-2xl">
            @if($title)
                <h2
                    class="text-3xl md:text-4xl font-bold mb-4"
                    style="
                        color: var(--mosaic-on-surface);
                        font-family: var(--mosaic-font-headline);
                    "
                >
                    {{ $title }}
                </h2>
            @endif

            @if($description)
                <p
                    class="text-lg leading-relaxed"
                    style="color: var(--mosaic-on-surface-variant);"
                >
                    {{ $description }}
                </p>
            @endif
        </div>
    @endif

    {{-- Features Grid/Vertical --}}
    @if($layout === 'vertical')
        <div class="space-y-6 max-w-2xl">
            @forelse($features as $index => $feature)
                <div
                    @class([
                        'mosaic-card flex gap-4',
                        'animate-fade-in' => $animation === 'fade-in',
                        'animate-slide-up' => $animation === 'slide-up',
                        'animate-zoom' => $animation === 'zoom',
                        'animate-bounce-in' => $animation === 'bounce',
                    ])
                    style="
                        background-color: var(--mosaic-surface-container);
                        animation-delay: {{ $index * 100 }}ms;
                    "
                >
                    {{-- Icon --}}
                    @if(isset($feature['icon']))
                        <div class="flex-shrink-0 text-4xl">
                            {{ $feature['icon'] }}
                        </div>
                    @endif

                    {{-- Content --}}
                    <div class="flex-1">
                        @if(isset($feature['title']))
                            <h3
                                class="text-xl font-bold mb-2"
                                style="color: var(--mosaic-on-surface);"
                            >
                                {{ $feature['title'] }}
                            </h3>
                        @endif

                        @if(isset($feature['description']))
                            <p
                                class="text-base leading-relaxed"
                                style="color: var(--mosaic-on-surface-variant);"
                            >
                                {{ $feature['description'] }}
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <p style="color: var(--mosaic-on-surface-variant);">No features configured</p>
            @endforelse
        </div>
    @else
        {{-- Grid Layout --}}
        <div class="grid {{ $gridClass }} gap-6">
            @forelse($features as $index => $feature)
                <div
                    @class([
                        'mosaic-card text-center',
                        'animate-fade-in' => $animation === 'fade-in',
                        'animate-slide-up' => $animation === 'slide-up',
                        'animate-zoom' => $animation === 'zoom',
                        'animate-bounce-in' => $animation === 'bounce',
                    ])
                    style="
                        background-color: var(--mosaic-surface-container);
                        animation-delay: {{ $index * 100 }}ms;
                    "
                >
                    {{-- Icon --}}
                    @if(isset($feature['icon']))
                        <div class="text-5xl mb-4">
                            {{ $feature['icon'] }}
                        </div>
                    @endif

                    {{-- Title --}}
                    @if(isset($feature['title']))
                        <h3
                            class="text-xl font-bold mb-2"
                            style="color: var(--mosaic-on-surface);"
                        >
                            {{ $feature['title'] }}
                        </h3>
                    @endif

                    {{-- Description --}}
                    @if(isset($feature['description']))
                        <p
                            class="text-base leading-relaxed"
                            style="color: var(--mosaic-on-surface-variant);"
                        >
                            {{ $feature['description'] }}
                        </p>
                    @endif
                </div>
            @empty
                <div class="col-span-full py-12 text-center">
                    <p style="color: var(--mosaic-on-surface-variant);">No features configured</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- Admin Hint --}}
    @if($customizable && auth()->check())
        <div class="mt-12 pt-8 max-w-full text-center" style="border-top: 1px solid var(--mosaic-outline-variant); opacity: 0.6;">
            <span class="mosaic-text-label text-xs">
                ✨ Customize: Add features, icons, animations, layout, columns
            </span>
        </div>
    @endif
</section>

<style scoped>
    .grid { display: grid; }
    .gap-6 { gap: 1.5rem; }
    .space-y-6 > * + * { margin-top: 1.5rem; }
    .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .md\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .lg\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .lg\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }

    .py-12 { padding-top: 3rem; padding-bottom: 3rem; }
    .py-16 { padding-top: 4rem; padding-bottom: 4rem; }
    .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
    .px-12 { padding-left: 3rem; padding-right: 3rem; }

    .mb-12 { margin-bottom: 3rem; }
    .mb-4 { margin-bottom: 1rem; }
    .mb-2 { margin-bottom: 0.5rem; }
    .mt-12 { margin-top: 3rem; }
    .pt-8 { padding-top: 2rem; }

    .max-w-2xl { max-width: 42rem; }
    .max-w-full { max-width: 100%; }

    .text-3xl { font-size: 1.875rem; }
    .text-4xl { font-size: 2.25rem; }
    .text-5xl { font-size: 3rem; }
    .text-xl { font-size: 1.25rem; }
    .text-lg { font-size: 1.125rem; }
    .text-base { font-size: 1rem; }
    .text-xs { font-size: 0.75rem; }

    .font-bold { font-weight: 700; }

    .leading-relaxed { line-height: 1.625; }

    .flex { display: flex; }
    .flex-col { flex-direction: column; }
    .gap-4 { gap: 1rem; }

    .flex-shrink-0 { flex-shrink: 0; }
    .flex-1 { flex: 1; }

    .text-center { text-align: center; }

    .col-span-full { grid-column: 1 / -1; }

    {{-- Animations --}}
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes zoomIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes bounceIn {
        0% {
            opacity: 0;
            transform: scale(0.8);
        }
        50% {
            opacity: 1;
            transform: scale(1.05);
        }
        100% {
            opacity: 1;
            transform: scale(1);
        }
    }

    .animate-fade-in {
        animation: fadeIn 0.6s ease-out forwards;
        opacity: 0;
    }

    .animate-slide-up {
        animation: slideUp 0.6s ease-out forwards;
        opacity: 0;
    }

    .animate-zoom {
        animation: zoomIn 0.6s ease-out forwards;
        opacity: 0;
    }

    .animate-bounce-in {
        animation: bounceIn 0.6s ease-out forwards;
        opacity: 0;
    }

    @media (max-width: 768px) {
        .md\:text-4xl { font-size: 2.25rem; }
        .md\:py-16 { padding-top: 4rem; padding-bottom: 4rem; }
        .md\:px-12 { padding-left: 3rem; padding-right: 3rem; }
    }
</style>
