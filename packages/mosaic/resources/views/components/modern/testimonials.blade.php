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
        1 => 'mx-auto max-w-2xl grid-cols-1',
        2 => 'grid-cols-1 md:grid-cols-2',
        3 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    ];

    $gridClass = $gridClasses[$columns] ?? $gridClasses[2];
@endphp

<section class="px-6 py-12 md:px-12 md:py-16">
    @if ($title)
        <div class="mx-auto mb-12 max-w-2xl text-center">
            <h2 class="mb-4 text-3xl font-bold text-gray-900 md:text-4xl">
                {{ $title }}
            </h2>
        </div>
    @endif

    @if ($displayMode === 'carousel')
        <div class="mosaic-testimonials-carousel relative mx-auto max-w-2xl">
            <div class="relative overflow-hidden rounded-2xl">
                <div
                    class="carousel-container flex transition-transform duration-300 ease-in-out"
                >
                    @forelse ($testimonials as $index => $testimonial)
                        <div class="carousel-slide min-w-full">
                            <div class="h-full rounded-2xl bg-gray-50 p-8">
                                <div
                                    class="mb-4 font-serif text-5xl leading-none text-indigo-200"
                                >
                                    &ldquo;
                                </div>

                                <blockquote class="mb-6">
                                    <p
                                        class="text-lg italic leading-relaxed text-gray-700"
                                    >
                                        {{ $testimonial['quote'] }}
                                    </p>
                                </blockquote>

                                <div
                                    class="flex items-center gap-4 border-t border-gray-200 pt-6"
                                >
                                    @if (isset($testimonial['avatar']))
                                        <div class="text-3xl">
                                            {{ $testimonial['avatar'] }}
                                        </div>
                                    @endif

                                    <div>
                                        @if (isset($testimonial['author']))
                                            <p class="font-bold text-gray-900">
                                                {{ $testimonial['author'] }}
                                            </p>
                                        @endif

                                        @if (isset($testimonial['role']))
                                            <p class="text-sm text-gray-500">
                                                {{ $testimonial['role'] }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="w-full py-12 text-center">
                            <p class="text-gray-500">
                                No testimonials configured
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>

            @if (count($testimonials) > 1)
                <button
                    class="carousel-prev absolute left-0 top-1/2 -translate-x-12 -translate-y-1/2 text-2xl text-gray-600 hover:text-gray-900"
                    onclick="slideCarousel(this, -1)"
                >
                    ←
                </button>
                <button
                    class="carousel-next absolute right-0 top-1/2 -translate-y-1/2 translate-x-12 text-2xl text-gray-600 hover:text-gray-900"
                    onclick="slideCarousel(this, 1)"
                >
                    →
                </button>

                <div class="mt-6 flex justify-center gap-2">
                    @for ($index = 0; $index < count($testimonials); $index++)
                        <button
                            class="carousel-dot h-2.5 w-2.5 rounded-full transition-all"
                            style="
                                background-color: {{ $index === 0 ? '#4f46e5' : '#d1d5db' }};
                            "
                            onclick="goToSlide(this, {{ $index }})"
                        ></button>
                    @endfor
                </div>
            @endif
        </div>
    @else
        <div class="{{ $gridClass }} grid gap-6">
            @forelse ($testimonials as $testimonial)
                <div class="rounded-2xl bg-gray-50 p-8">
                    <div
                        class="mb-4 font-serif text-5xl leading-none text-indigo-200"
                    >
                        &ldquo;
                    </div>

                    <blockquote class="mb-6">
                        <p class="text-lg italic leading-relaxed text-gray-700">
                            {{ $testimonial['quote'] }}
                        </p>
                    </blockquote>

                    <div
                        class="flex items-center gap-4 border-t border-gray-200 pt-6"
                    >
                        @if (isset($testimonial['avatar']))
                            <div class="text-3xl">
                                {{ $testimonial['avatar'] }}
                            </div>
                        @endif

                        <div>
                            @if (isset($testimonial['author']))
                                <p class="font-bold text-gray-900">
                                    {{ $testimonial['author'] }}
                                </p>
                            @endif

                            @if (isset($testimonial['role']))
                                <p class="text-sm text-gray-500">
                                    {{ $testimonial['role'] }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center">
                    <p class="text-gray-500">No testimonials configured</p>
                </div>
            @endforelse
        </div>
    @endif

    @if ($customizable && auth()->check())
        <div class="mt-12 border-t border-gray-100 pt-8 text-center opacity-60">
            <span class="text-xs text-gray-500">
                ✨ Customize: Add testimonials, layout mode (grid/carousel),
                columns
            </span>
        </div>
    @endif
</section>

<script>
    function slideCarousel(button, direction) {
        const carousel = button.closest('.mosaic-testimonials-carousel')
        const container = carousel.querySelector('.carousel-container')
        const slides = carousel.querySelectorAll('.carousel-slide')
        const currentOffset =
            parseInt(
                container.style.transform?.replace('translateX(', '') ?? '0',
            ) || 0
        const currentIndex = Math.round(-currentOffset / 100)

        let newIndex = currentIndex + direction
        if (newIndex < 0) newIndex = slides.length - 1
        if (newIndex >= slides.length) newIndex = 0

        container.style.transform = `translateX(${-newIndex * 100}%)`
        updateDots(carousel, newIndex)
    }

    function goToSlide(dotButton, index) {
        const carousel = dotButton.closest('.mosaic-testimonials-carousel')
        const container = carousel.querySelector('.carousel-container')
        container.style.transform = `translateX(${-index * 100}%)`
        updateDots(carousel, index)
    }

    function updateDots(carousel, activeIndex) {
        carousel.querySelectorAll('.carousel-dot').forEach((dot, index) => {
            dot.style.backgroundColor =
                index === activeIndex ? '#4f46e5' : '#d1d5db'
        })
    }
</script>
