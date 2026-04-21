{{--
    Modern Process Steps Widget
    
    Props:
    - title (string): Section heading
    - subtitle (string): Section description
    - steps (array): Array of step objects { number, title, description, icon }
    - layout (string): horizontal or vertical layout
    - customizable (bool): Show admin hints
--}}

@props([
    'title' => 'Our Process',
    'subtitle' => 'Four simple steps to get started',
    'steps' => [
        [
            'number' => '1',
            'title' => 'Discovery',
            'description' => 'We learn about your goals and vision',
            'icon' => '🔍',
        ],
        [
            'number' => '2',
            'title' => 'Strategy',
            'description' => 'We create a tailored roadmap',
            'icon' => '📋',
        ],
        [
            'number' => '3',
            'title' => 'Execution',
            'description' => 'We build and deliver results',
            'icon' => '⚙️',
        ],
        [
            'number' => '4',
            'title' => 'Support',
            'description' => 'We provide ongoing assistance',
            'icon' => '🤝',
        ],
    ],
    'layout' => 'horizontal',
    'customizable' => true,
])

<section class="px-6 py-12 md:px-12 md:py-16">
    @if ($title)
        <div class="mx-auto mb-12 max-w-2xl text-center">
            <h2 class="mb-3 text-3xl font-bold text-gray-900 md:text-4xl">
                {{ $title }}
            </h2>
            @if ($subtitle)
                <p class="text-lg text-gray-500">{{ $subtitle }}</p>
            @endif
        </div>
    @endif

    @if ($layout === 'horizontal')
        <div class="relative mx-auto max-w-5xl">
            {{-- Connecting line --}}
            <div
                class="absolute left-0 right-0 top-12 hidden h-0.5 bg-gradient-to-r from-indigo-300 via-purple-300 to-indigo-300 md:block"
            ></div>

            <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
                @forelse ($steps as $step)
                    <div class="relative text-center">
                        {{-- Circle with badge --}}
                        <div class="relative z-10 mx-auto mb-4 h-24 w-24">
                            <div
                                class="flex h-24 w-24 items-center justify-center rounded-full border-2 border-indigo-200 bg-white text-4xl shadow-sm"
                            >
                                {{ $step['icon'] ?? $step['number'] }}
                            </div>
                            <div
                                class="absolute -right-1 -top-1 flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white"
                            >
                                {{ $step['number'] }}
                            </div>
                        </div>

                        @if (isset($step['title']))
                            <h3 class="mb-1 text-base font-bold text-gray-900">
                                {{ $step['title'] }}
                            </h3>
                        @endif

                        @if (isset($step['description']))
                            <p class="text-sm text-gray-500">
                                {{ $step['description'] }}
                            </p>
                        @endif
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center">
                        <p class="text-gray-500">No steps configured</p>
                    </div>
                @endforelse
            </div>
        </div>
    @else
        <div class="mx-auto max-w-3xl space-y-8">
            @forelse ($steps as $step)
                <div class="flex gap-6">
                    <div
                        class="relative flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full border-2 border-indigo-200 bg-white text-2xl shadow-sm"
                    >
                        {{ $step['icon'] ?? $step['number'] }}
                        <div
                            class="absolute -right-1 -top-1 flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white"
                        >
                            {{ $step['number'] }}
                        </div>
                    </div>

                    <div class="flex-grow pt-2">
                        @if (isset($step['title']))
                            <h3 class="mb-1 text-lg font-bold text-gray-900">
                                {{ $step['title'] }}
                            </h3>
                        @endif

                        @if (isset($step['description']))
                            <p class="text-gray-500">
                                {{ $step['description'] }}
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-12 text-center">
                    <p class="text-gray-500">No steps configured</p>
                </div>
            @endforelse
        </div>
    @endif

    @if ($customizable && auth()->check())
        <div class="mt-12 border-t border-gray-100 pt-8 text-center opacity-60">
            <span class="text-xs text-gray-500">
                ✨ Customize: Add steps, change icons, titles, and layout
            </span>
        </div>
    @endif
</section>
