{{--
  Modern Testimonials Widget

  Props:
    - title (string): Section heading
    - testimonials (array): Array of testimonial objects
    - columns (int): Number of columns (1,2,3) - Default: 2
    - displayMode (string): 'grid|carousel' - Default: 'grid'
    - customizable (bool): Show admin hints
--}}

@props([
    'title' => 'What Customers Say',
    'testimonials' => [
        [
            'quote' => 'Amazing experience! Capell made it so easy to manage our content.',
            'author' => 'Sarah Johnson',
            'role' => 'Marketing Manager',
            'avatar' => '👩‍💼',
        ],
        [
            'quote' => 'Switched from other CMS platforms. Best decision ever!',
            'author' => 'Mike Chen',
            'role' => 'CEO',
            'avatar' => '👨‍💼',
        ],
    ],
    'columns' => 2,
    'displayMode' => 'grid',
    'customizable' => true,
])

@php
    $gridClasses = [
        1 => 'grid-cols-1 max-w-2xl mx-auto',
        2 => 'grid-cols-1 md:grid-cols-2',
        3 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    ];

    $gridClass = $gridClasses[$columns] ?? $gridClasses[2];
@endphp

<section class="mosaic-testimonials py-12 md:py-16 px-6 md:px-12">
    {{-- Header --}}
    @if($title)
        <div class="mb-12 text-center max-w-2xl mx-auto">
            <h2
                class="text-3xl md:text-4xl font-bold mb-4"
                style="
                    color: var(--mosaic-on-surface);
                    font-family: var(--mosaic-font-headline);
                "
            >
                {{ $title }}
            </h2>
        </div>
    @endif

    @if($displayMode === 'carousel')
        {{-- Carousel Mode --}}
        <div class="mosaic-testimonials-carousel relative max-w-2xl mx-auto">
            <div class="relative overflow-hidden rounded-lg">
                <div class="carousel-container" style="display: flex; transition: transform 0.3s ease;">
                    @forelse($testimonials as $index => $testimonial)
                        <div
                            class="carousel-slide"
                            style="
                                min-width: 100%;
                                display: flex;
                                flex-direction: column;
                            "
                        >
                            <div
                                class="mosaic-card h-full"
                                style="background-color: var(--mosaic-surface-container);"
                            >
                                {{-- Quote Mark --}}
                                <div class="text-4xl mb-4" style="color: var(--mosaic-tertiary); opacity: 0.3;">
                                    "
                                </div>

                                {{-- Quote --}}
                                <blockquote class="mb-6">
                                    <p
                                        class="text-lg leading-relaxed italic"
                                        style="color: var(--mosaic-on-surface);"
                                    >
                                        {{ $testimonial['quote'] }}
                                    </p>
                                </blockquote>

                                {{-- Author Info --}}
                                <div class="flex items-center gap-4 pt-6 mt-auto" style="border-top: 1px solid var(--mosaic-outline-variant);">
                                    {{-- Avatar --}}
                                    @if(isset($testimonial['avatar']))
                                        <div class="text-3xl">
                                            {{ $testimonial['avatar'] }}
                                        </div>
                                    @endif

                                    <div>
                                        @if(isset($testimonial['author']))
                                            <p
                                                class="font-bold text-base"
                                                style="color: var(--mosaic-on-surface);"
                                            >
                                                {{ $testimonial['author'] }}
                                            </p>
                                        @endif

                                        @if(isset($testimonial['role']))
                                            <p
                                                class="text-sm"
                                                style="color: var(--mosaic-on-surface-variant);"
                                            >
                                                {{ $testimonial['role'] }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="w-full py-12 text-center">
                            <p style="color: var(--mosaic-on-surface-variant);">No testimonials configured</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Navigation Buttons --}}
            @if(count($testimonials) > 1)
                <button
                    class="carousel-prev absolute left-0 top-1/2 -translate-y-1/2 -translate-x-16 text-2xl"
                    style="color: var(--mosaic-on-surface); cursor: pointer;"
                    onclick="slideCarousel(this, -1)"
                >
                    ←
                </button>
                <button
                    class="carousel-next absolute right-0 top-1/2 -translate-y-1/2 translate-x-16 text-2xl"
                    style="color: var(--mosaic-on-surface); cursor: pointer;"
                    onclick="slideCarousel(this, 1)"
                >
                    →
                </button>

                {{-- Indicator Dots --}}
                <div class="flex justify-center gap-2 mt-6">
                    @for ($index = 0; $index < count($testimonials); $index++)
                        <button
                            class="carousel-dot w-3 h-3 rounded-full transition-all"
                            style="
                                background-color: {{ $index === 0 ? 'var(--mosaic-primary)' : 'var(--mosaic-outline)' }};
                                cursor: pointer;
                            "
                            onclick="goToSlide(this, {{ $index }})"
                        ></button>
                    @endfor
                </div>
            @endif
        </div>
    @else
        {{-- Grid Mode --}}
        <div class="grid {{ $gridClass }} gap-6">
            @forelse($testimonials as $testimonial)
                <div
                    class="mosaic-card"
                    style="background-color: var(--mosaic-surface-container);"
                >
                    {{-- Quote Mark --}}
                    <div class="text-4xl mb-4" style="color: var(--mosaic-tertiary); opacity: 0.3;">
                        "
                    </div>

                    {{-- Quote --}}
                    <blockquote class="mb-6">
                        <p
                            class="text-lg leading-relaxed italic"
                            style="color: var(--mosaic-on-surface);"
                        >
                            {{ $testimonial['quote'] }}
                        </p>
                    </blockquote>

                    {{-- Author Info --}}
                    <div class="flex items-center gap-4 pt-6" style="border-top: 1px solid var(--mosaic-outline-variant);">
                        {{-- Avatar --}}
                        @if(isset($testimonial['avatar']))
                            <div class="text-3xl">
                                {{ $testimonial['avatar'] }}
                            </div>
                        @endif

                        <div>
                            @if(isset($testimonial['author']))
                                <p
                                    class="font-bold text-base"
                                    style="color: var(--mosaic-on-surface);"
                                >
                                    {{ $testimonial['author'] }}
                                </p>
                            @endif

                            @if(isset($testimonial['role']))
                                <p
                                    class="text-sm"
                                    style="color: var(--mosaic-on-surface-variant);"
                                >
                                    {{ $testimonial['role'] }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center">
                    <p style="color: var(--mosaic-on-surface-variant);">No testimonials configured</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- Admin Hint --}}
    @if($customizable && auth()->check())
        <div class="mt-12 pt-8 max-w-full text-center" style="border-top: 1px solid var(--mosaic-outline-variant); opacity: 0.6;">
            <span class="mosaic-text-label text-xs">
                ✨ Customize: Add testimonials, layout mode (grid/carousel), columns
            </span>
        </div>
    @endif
</section>

<script>
    function slideCarousel(button, direction) {
        const carousel = button.closest('.mosaic-testimonials-carousel');
        const container = carousel.querySelector('.carousel-container');
        const slides = carousel.querySelectorAll('.carousel-slide');
        let currentIndex = 0;

        slides.forEach((slide, index) => {
            if (slide.style.display !== 'none') {
                currentIndex = index;
            }
        });

        currentIndex += direction;
        if (currentIndex < 0) currentIndex = slides.length - 1;
        if (currentIndex >= slides.length) currentIndex = 0;

        const offset = -currentIndex * 100;
        container.style.transform = `translateX(${offset}%)`;

        updateDots(carousel, currentIndex);
    }

    function goToSlide(dotButton, index) {
        const carousel = dotButton.closest('.mosaic-testimonials-carousel');
        const container = carousel.querySelector('.carousel-container');
        const offset = -index * 100;
        container.style.transform = `translateX(${offset}%)`;

        updateDots(carousel, index);
    }

    function updateDots(carousel, activeIndex) {
        const dots = carousel.querySelectorAll('.carousel-dot');
        dots.forEach((dot, index) => {
            if (index === activeIndex) {
                dot.style.backgroundColor = 'var(--mosaic-primary)';
            } else {
                dot.style.backgroundColor = 'var(--mosaic-outline)';
            }
        });
    }
</script>

<style scoped>
    .grid { display: grid; }
    .gap-6 { gap: 1.5rem; }

    .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .md\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .lg\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }

    .max-w-2xl { max-width: 42rem; }
    .mx-auto { margin-left: auto; margin-right: auto; }
    .max-w-full { max-width: 100%; }

    .py-12 { padding-top: 3rem; padding-bottom: 3rem; }
    .py-16 { padding-top: 4rem; padding-bottom: 4rem; }
    .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
    .px-12 { padding-left: 3rem; padding-right: 3rem; }

    .mb-12 { margin-bottom: 3rem; }
    .mb-6 { margin-bottom: 1.5rem; }
    .mb-4 { margin-bottom: 1rem; }
    .mt-12 { margin-top: 3rem; }
    .pt-6 { padding-top: 1.5rem; }
    .pt-8 { padding-top: 2rem; }

    .text-center { text-align: center; }

    .text-3xl { font-size: 1.875rem; }
    .text-4xl { font-size: 2.25rem; }
    .text-lg { font-size: 1.125rem; }
    .text-base { font-size: 1rem; }
    .text-sm { font-size: 0.875rem; }
    .text-xs { font-size: 0.75rem; }

    .font-bold { font-weight: 700; }

    .italic { font-style: italic; }
    .leading-relaxed { line-height: 1.625; }

    .flex { display: flex; }
    .items-center { align-items: center; }
    .gap-4 { gap: 1rem; }

    .col-span-full { grid-column: 1 / -1; }

    blockquote { margin: 0; }

    {{-- Carousel Styles --}}
    .mosaic-testimonials-carousel { position: relative; }

    .carousel-container {
        will-change: transform;
    }

    .carousel-prev,
    .carousel-next {
        z-index: 10;
        padding: 0.5rem;
        background: none;
        border: none;
        transition: opacity 0.2s;
    }

    .carousel-prev:hover,
    .carousel-next:hover {
        opacity: 0.7;
    }

    .carousel-dot {
        padding: 0;
        background: none;
        border: none;
        transition: background-color 0.3s;
    }

    .carousel-slide {
        flex-shrink: 0;
        width: 100%;
    }

    .mosaic-card {
        display: flex;
        flex-direction: column;
    }

    @media (max-width: 768px) {
        .md\:text-4xl { font-size: 2.25rem; }
        .md\:py-16 { padding-top: 4rem; padding-bottom: 4rem; }
        .md\:px-12 { padding-left: 3rem; padding-right: 3rem; }

        .carousel-prev,
        .carousel-next {
            position: static !important;
            transform: none !important;
            display: inline-block;
            margin: 0 0.5rem;
        }
    }
</style>
