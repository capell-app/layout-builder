{{--
    Modern Alternating Content Section Widget
    
    Props:
    - title (string): Section heading
    - sections (array): Array of content objects { heading, description, image, position }
    - customizable (bool): Show admin hints
--}}

@props([
    'title' => 'How It Works',
    'sections' => [
        [
            'heading' => 'Step 1: Create',
            'description' => 'Start building your content with our intuitive drag-and-drop editor. No coding required.',
            'image' => '📝',
            'position' => 'left',
        ],
        [
            'heading' => 'Step 2: Customize',
            'description' => 'Personalize colors, fonts, and layouts to match your brand perfectly.',
            'image' => '🎨',
            'position' => 'right',
        ],
        [
            'heading' => 'Step 3: Publish',
            'description' => 'Deploy your content instantly with one click. Real-time updates available.',
            'image' => '🚀',
            'position' => 'left',
        ],
    ],
    'customizable' => true,
])

<section class="px-6 py-12 md:px-12 md:py-16">
    @if ($title)
        <div class="mx-auto mb-12 max-w-2xl text-center">
            <h2 class="text-3xl font-bold text-gray-900 md:text-4xl">
                {{ $title }}
            </h2>
        </div>
    @endif

    <div class="mx-auto max-w-5xl space-y-16">
        @forelse ($sections as $index => $section)
            @php
                $isRight = ($section['position'] ?? 'left') === 'right';
            @endphp

            <div class="grid grid-cols-1 items-center gap-8 md:grid-cols-2">
                {{-- Image Column --}}
                @if (isset($section['image']))
                    <div
                        @class(['flex min-h-64 items-center justify-center rounded-2xl bg-gray-50 p-8 text-8xl', 'md:order-last' => $isRight])
                    >
                        {{ $section['image'] }}
                    </div>
                @endif

                {{-- Content Column --}}
                <div>
                    {{-- Step Number Badge --}}
                    <div
                        class="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white"
                    >
                        {{ $index + 1 }}
                    </div>

                    @if (isset($section['heading']))
                        <h3 class="mb-3 text-2xl font-bold text-gray-900">
                            {{ $section['heading'] }}
                        </h3>
                    @endif

                    @if (isset($section['description']))
                        <p class="text-base leading-relaxed text-gray-600">
                            {{ $section['description'] }}
                        </p>
                    @endif
                </div>
            </div>
        @empty
            <div class="py-12 text-center">
                <p class="text-gray-500">No content sections configured</p>
            </div>
        @endforelse
    </div>

    @if ($customizable && auth()->check())
        <div class="mt-12 border-t border-gray-100 pt-8 text-center opacity-60">
            <span class="text-xs text-gray-500">
                ✨ Customize: Add sections, change images, toggle positions
            </span>
        </div>
    @endif
</section>
