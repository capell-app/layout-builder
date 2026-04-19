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

<section class="mosaic-process px-6 py-12 md:px-12 md:py-16">
    {{-- Header --}}
    @if ($title)
        <div class="mx-auto mb-12 max-w-2xl text-center">
            <h2
                class="mb-3 text-3xl font-bold md:text-4xl"
                style="
                    color: var(--mosaic-on-surface);
                    font-family: var(--mosaic-font-headline);
                "
            >
                {{ $title }}
            </h2>
            @if ($subtitle)
                <p
                    class="text-lg"
                    style="color: var(--mosaic-on-surface-variant)"
                >
                    {{ $subtitle }}
                </p>
            @endif
        </div>
    @endif

    {{-- Steps Container --}}
    @if ($layout === 'horizontal')
        {{-- Horizontal Timeline --}}
        <div class="relative mx-auto max-w-5xl">
            {{-- Timeline Line --}}
            <div
                class="absolute left-0 right-0 top-12 hidden h-1 md:block"
                style="
                    background: linear-gradient(
                        to right,
                        var(--mosaic-primary),
                        var(--mosaic-secondary),
                        var(--mosaic-tertiary)
                    );
                    transform: translateY(-50%);
                "
            ></div>

            {{-- Steps Grid --}}
            <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                @forelse ($steps as $index => $step)
                    <div class="relative text-center">
                        {{-- Step Circle --}}
                        <div
                            class="relative z-10 mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full"
                            style="
                                background-color: var(
                                    --mosaic-surface-container
                                );
                                border: 3px solid var(--mosaic-primary);
                            "
                        >
                            <div class="text-4xl">
                                {{ $step['icon'] ?? $step['number'] }}
                            </div>
                        </div>

                        {{-- Step Number Badge --}}
                        <div
                            class="absolute right-0 top-0 flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold"
                            style="
                                background-color: var(--mosaic-primary);
                                color: var(--mosaic-on-primary);
                            "
                        >
                            {{ $step['number'] }}
                        </div>

                        {{-- Content --}}
                        @if (isset($step['title']))
                            <h3
                                class="mb-2 text-lg font-bold"
                                style="color: var(--mosaic-on-surface)"
                            >
                                {{ $step['title'] }}
                            </h3>
                        @endif

                        @if (isset($step['description']))
                            <p
                                class="text-sm"
                                style="color: var(--mosaic-on-surface-variant)"
                            >
                                {{ $step['description'] }}
                            </p>
                        @endif
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center">
                        <p style="color: var(--mosaic-on-surface-variant)">
                            No steps configured
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    @else
        {{-- Vertical Layout --}}
        <div class="mx-auto max-w-3xl space-y-8">
            @forelse ($steps as $index => $step)
                <div class="flex gap-6">
                    {{-- Circle --}}
                    <div
                        class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full text-2xl"
                        style="
                            background-color: var(--mosaic-surface-container);
                            border: 2px solid var(--mosaic-primary);
                        "
                    >
                        {{ $step['icon'] ?? $step['number'] }}
                    </div>

                    {{-- Content --}}
                    <div class="flex-grow">
                        @if (isset($step['title']))
                            <h3
                                class="mb-2 text-lg font-bold"
                                style="color: var(--mosaic-on-surface)"
                            >
                                {{ $step['title'] }}
                            </h3>
                        @endif

                        @if (isset($step['description']))
                            <p
                                class="text-base"
                                style="color: var(--mosaic-on-surface-variant)"
                            >
                                {{ $step['description'] }}
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-12 text-center">
                    <p style="color: var(--mosaic-on-surface-variant)">
                        No steps configured
                    </p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- Admin Hint --}}
    @if ($customizable && auth()->check())
        <div
            class="mt-12 max-w-full pt-8 text-center"
            style="
                border-top: 1px solid var(--mosaic-outline-variant);
                opacity: 0.6;
            "
        >
            <span class="mosaic-text-label text-xs">
                ✨ Customize: Add steps, change icons, titles, and layout
            </span>
        </div>
    @endif
</section>

<style scoped>
    .grid {
        display: grid;
    }
    .grid-cols-1 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }
    .md\:grid-cols-4 {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .md\:block {
        display: block;
    }
    .hidden {
        display: none;
    }

    .gap-6 {
        gap: 1.5rem;
    }
    .space-y-8 > * + * {
        margin-top: 2rem;
    }

    .max-w-2xl {
        max-width: 42rem;
    }
    .max-w-5xl {
        max-width: 64rem;
    }
    .max-w-3xl {
        max-width: 48rem;
    }
    .mx-auto {
        margin-left: auto;
        margin-right: auto;
    }
    .max-w-full {
        max-width: 100%;
    }

    .py-12 {
        padding-top: 3rem;
        padding-bottom: 3rem;
    }
    .py-16 {
        padding-top: 4rem;
        padding-bottom: 4rem;
    }
    .px-6 {
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }
    .px-12 {
        padding-left: 3rem;
        padding-right: 3rem;
    }

    .mb-12 {
        margin-bottom: 3rem;
    }
    .mb-4 {
        margin-bottom: 1rem;
    }
    .mb-3 {
        margin-bottom: 0.75rem;
    }
    .mb-2 {
        margin-bottom: 0.5rem;
    }
    .mt-12 {
        margin-top: 3rem;
    }
    .pt-8 {
        padding-top: 2rem;
    }

    .text-center {
        text-align: center;
    }

    .text-3xl {
        font-size: 1.875rem;
    }
    .text-4xl {
        font-size: 2.25rem;
    }
    .text-lg {
        font-size: 1.125rem;
    }
    .text-base {
        font-size: 1rem;
    }
    .text-sm {
        font-size: 0.875rem;
    }
    .text-xs {
        font-size: 0.75rem;
    }
    .text-2xl {
        font-size: 1.5rem;
    }
    .text-4xl {
        font-size: 2.25rem;
    }

    .font-bold {
        font-weight: 700;
    }

    .relative {
        position: relative;
    }
    .absolute {
        position: absolute;
    }

    .flex {
        display: flex;
    }
    .flex-shrink-0 {
        flex-shrink: 0;
    }
    .flex-grow {
        flex-grow: 1;
    }
    .items-center {
        align-items: center;
    }
    .justify-center {
        justify-content: center;
    }

    .w-24 {
        width: 6rem;
    }
    .h-24 {
        height: 6rem;
    }
    .w-8 {
        width: 2rem;
    }
    .h-8 {
        height: 2rem;
    }
    .w-16 {
        width: 4rem;
    }
    .h-16 {
        height: 4rem;
    }

    .rounded-full {
        border-radius: 9999px;
    }

    .top-0 {
        top: 0;
    }
    .right-0 {
        right: 0;
    }
    .top-12 {
        top: 3rem;
    }
    .left-0 {
        left: 0;
    }

    .h-1 {
        height: 0.25rem;
    }
    .z-10 {
        z-index: 10;
    }

    .relative {
        position: relative;
    }

    @media (max-width: 768px) {
        .md\:text-4xl {
            font-size: 2.25rem;
        }
        .md\:py-16 {
            padding-top: 4rem;
            padding-bottom: 4rem;
        }
        .md\:px-12 {
            padding-left: 3rem;
            padding-right: 3rem;
        }
        .md\:block {
            display: none;
        }
    }
</style>
