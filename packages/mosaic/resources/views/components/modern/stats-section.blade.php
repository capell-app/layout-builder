{{--
    Modern Stats Section Widget
    
    Props:
    - title (string): Section heading
    - subtitle (string): Section description
    - stats (array): Array of stat objects { icon, label, value, color }
    - layout (string): horizontal or vertical layout
    - customizable (bool): Show admin hints
--}}

@props([
    'title' => 'By The Numbers',
    'subtitle' => 'Proven results that speak for themselves',
    'stats' => [
        [
            'icon' => '👥',
            'label' => 'Active Users',
            'value' => '10,000+',
            'color' => 'primary',
        ],
        [
            'icon' => '🚀',
            'label' => 'Projects Launched',
            'value' => '500+',
            'color' => 'secondary',
        ],
        [
            'icon' => '⭐',
            'label' => 'Satisfaction Rate',
            'value' => '98%',
            'color' => 'tertiary',
        ],
        [
            'icon' => '🌍',
            'label' => 'Countries',
            'value' => '50+',
            'color' => 'primary',
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

    <div
        class="{{ $layout === 'vertical' ? 'max-w-md grid-cols-1' : 'max-w-5xl grid-cols-2 md:grid-cols-4' }} mx-auto grid gap-6"
    >
        @forelse ($stats as $stat)
            <div class="rounded-2xl bg-gray-50 p-8 text-center">
                @if (isset($stat['icon']))
                    <div class="mb-3 text-4xl">{{ $stat['icon'] }}</div>
                @endif

                @if (isset($stat['value']))
                    <p
                        class="mb-1 text-3xl font-bold text-indigo-600 md:text-4xl"
                    >
                        {{ $stat['value'] }}
                    </p>
                @endif

                @if (isset($stat['label']))
                    <p class="text-sm font-medium text-gray-500">
                        {{ $stat['label'] }}
                    </p>
                @endif
            </div>
        @empty
            <div class="col-span-full py-12 text-center">
                <p class="text-gray-500">No stats configured</p>
            </div>
        @endforelse
    </div>

    @if ($customizable && auth()->check())
        <div class="mt-12 border-t border-gray-100 pt-8 text-center opacity-60">
            <span class="text-xs text-gray-500">
                ✨ Customize: Add stats, change icons, values, and layout
            </span>
        </div>
    @endif
</section>
