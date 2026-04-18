{{--
  Modern Card Grid Widget

  Props:
    - title (string): Section heading
    - description (string): Section description
    - cards (array): Array of card objects
      Each card: { icon, title, description, link, image }
    - columns (int): Number of columns (2,3,4) - Default: 3
    - variant (string): 'default|elevated|glass' - Default: 'default'
    - accentColor (string): 'primary|secondary|tertiary' - Default: 'primary'
    - customizable (bool): Show admin hints
--}}

@props([
    'title' => 'Featured Widgets',
    'description' => 'Choose from our collection of modern, customizable components',
    'cards' => [
        ['icon' => '🎨', 'title' => 'Design System', 'description' => 'Modern tokens and components'],
        ['icon' => '⚡', 'title' => 'Performance', 'description' => 'Lightning-fast rendering'],
        ['icon' => '🔧', 'title' => 'Customizable', 'description' => 'Endless possibilities'],
    ],
    'columns' => 3,
    'variant' => 'default',
    'accentColor' => 'primary',
    'customizable' => true,
])

@php
    $gridClasses = [
        2 => 'grid-cols-1 md:grid-cols-2',
        3 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
        4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
    ];

    $gridClass = $gridClasses[$columns] ?? $gridClasses[3];

    $variantClasses = [
        'default' => 'mosaic-card',
        'elevated' => 'mosaic-card shadow-lg',
        'glass' => 'mosaic-bg-glass rounded-lg p-6',
    ];

    $variantClass = $variantClasses[$variant] ?? $variantClasses['default'];

    $accentColorMap = [
        'primary' => 'var(--mosaic-primary)',
        'secondary' => 'var(--mosaic-secondary)',
        'tertiary' => 'var(--mosaic-tertiary)',
    ];

    $accentColor = $accentColorMap[$accentColor] ?? $accentColorMap['primary'];
@endphp

<section class="mosaic-card-grid py-12 md:py-16 px-6 md:px-12">
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

    {{-- Grid --}}
    <div class="grid {{ $gridClass }} gap-6">
        @forelse($cards as $card)
            <div class="{{ $variantClass }}">
                {{-- Icon --}}
                @if(isset($card['icon']))
                    <div class="text-4xl mb-4">
                        {{ $card['icon'] }}
                    </div>
                @endif

                {{-- Image --}}
                @if(isset($card['image']))
                    <img
                        src="{{ $card['image'] }}"
                        alt="{{ $card['title'] ?? '' }}"
                        class="w-full h-40 object-cover rounded-md mb-4"
                    />
                @endif

                {{-- Title --}}
                @if(isset($card['title']))
                    <h3
                        class="text-xl font-bold mb-2"
                        style="color: var(--mosaic-on-surface);"
                    >
                        {{ $card['title'] }}
                    </h3>
                @endif

                {{-- Description --}}
                @if(isset($card['description']))
                    <p
                        class="text-base mb-4 leading-relaxed"
                        style="color: var(--mosaic-on-surface-variant);"
                    >
                        {{ $card['description'] }}
                    </p>
                @endif

                {{-- Link --}}
                @if(isset($card['link']))
                    <a
                        href="{{ $card['link']['url'] }}"
                        class="inline-flex items-center gap-2 font-semibold text-sm transition-all"
                        style="
                            color: {{ $accentColor }};
                            text-decoration: none;
                        "
                    >
                        {{ $card['link']['label'] ?? 'Learn More' }}
                        <span>→</span>
                    </a>
                @endif
            </div>
        @empty
            <div class="col-span-full py-12 text-center">
                <p class="mosaic-text-label mb-4">No cards configured</p>
                <p style="color: var(--mosaic-on-surface-variant);">
                    Add cards in the admin panel to display content
                </p>
            </div>
        @endforelse
    </div>

    {{-- Admin Hint --}}
    @if($customizable && auth()->check())
        <div class="mt-12 pt-8 max-w-full" style="border-top: 1px solid var(--mosaic-outline-variant); opacity: 0.6;">
            <span class="mosaic-text-label text-xs">
                ✨ Customize: Add/edit cards, change columns, variant, and accent color
            </span>
        </div>
    @endif
</section>

<style scoped>
    .grid { display: grid; }
    .gap-6 { gap: 1.5rem; }
    .gap-4 { gap: 1rem; }
    .gap-2 { gap: 0.5rem; }

    .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .md\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .lg\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .lg\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }

    .col-span-full { grid-column: 1 / -1; }

    .py-12 { padding-top: 3rem; padding-bottom: 3rem; }
    .py-16 { padding-top: 4rem; padding-bottom: 4rem; }
    .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
    .px-12 { padding-left: 3rem; padding-right: 3rem; }

    .mb-12 { margin-bottom: 3rem; }
    .mb-4 { margin-bottom: 1rem; }
    .mb-2 { margin-bottom: 0.5rem; }
    .mb-8 { margin-bottom: 2rem; }
    .mt-12 { margin-top: 3rem; }
    .pt-8 { padding-top: 2rem; }

    .max-w-2xl { max-width: 42rem; }

    .text-3xl { font-size: 1.875rem; }
    .text-4xl { font-size: 2.25rem; }
    .text-xl { font-size: 1.25rem; }
    .text-lg { font-size: 1.125rem; }
    .text-base { font-size: 1rem; }
    .text-sm { font-size: 0.875rem; }
    .text-xs { font-size: 0.75rem; }

    .font-bold { font-weight: 700; }
    .font-semibold { font-weight: 600; }

    .leading-relaxed { line-height: 1.625; }

    .inline-flex { display: inline-flex; }
    .flex { display: flex; }
    .items-center { align-items: center; }

    .w-full { width: 100%; }
    .h-40 { height: 10rem; }

    .object-cover { object-fit: cover; }
    .rounded-md { border-radius: var(--mosaic-radius-md); }

    .text-center { text-align: center; }

    .transition-all { transition: all var(--mosaic-transition-base); }

    @media (max-width: 768px) {
        .md\:text-4xl { font-size: 2.25rem; }
        .md\:px-12 { padding-left: 3rem; padding-right: 3rem; }
        .md\:py-16 { padding-top: 4rem; padding-bottom: 4rem; }
    }

    .shadow-lg {
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    }
</style>
