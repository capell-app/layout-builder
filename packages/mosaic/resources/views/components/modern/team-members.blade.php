{{--
  Modern Team Members Widget

  Props:
    - title (string): Section heading
    - members (array): Array of team member objects
    - columns (int): Number of columns (2,3,4) - Default: 3
    - customizable (bool): Show admin hints
--}}

@props([
    'title' => 'Our Team',
    'members' => [
        [
            'name' => 'Alex Morgan',
            'role' => 'Product Lead',
            'avatar' => '👨‍💻',
            'bio' => 'Creative designer with 5+ years experience',
        ],
        [
            'name' => 'Emma Davis',
            'role' => 'Engineering Manager',
            'avatar' => '👩‍🔬',
            'bio' => 'Full-stack developer and architect',
        ],
        [
            'name' => 'James Wilson',
            'role' => 'CEO & Co-founder',
            'avatar' => '🧑‍💼',
            'bio' => 'Serial entrepreneur and visionary',
        ],
    ],
    'columns' => 3,
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

<section class="mosaic-team-members py-12 md:py-16 px-6 md:px-12">
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

    {{-- Team Grid --}}
    <div class="grid {{ $gridClass }} gap-6">
        @forelse($members as $member)
            <div
                class="mosaic-card text-center overflow-hidden"
                style="background-color: var(--mosaic-surface-container);"
            >
                {{-- Avatar --}}
                @if(isset($member['avatar']))
                    <div
                        class="w-20 h-20 mx-auto mb-4 rounded-full flex items-center justify-center text-5xl"
                        style="background-color: var(--mosaic-surface-container-high);"
                    >
                        {{ $member['avatar'] }}
                    </div>
                @endif

                {{-- Name --}}
                @if(isset($member['name']))
                    <h3
                        class="text-lg font-bold mb-1"
                        style="color: var(--mosaic-on-surface);"
                    >
                        {{ $member['name'] }}
                    </h3>
                @endif

                {{-- Role --}}
                @if(isset($member['role']))
                    <p
                        class="text-sm font-semibold mb-3"
                        style="
                            color: var(--mosaic-tertiary);
                            text-transform: uppercase;
                            letter-spacing: 0.05em;
                        "
                    >
                        {{ $member['role'] }}
                    </p>
                @endif

                {{-- Bio --}}
                @if(isset($member['bio']))
                    <p
                        class="text-base leading-relaxed"
                        style="color: var(--mosaic-on-surface-variant);"
                    >
                        {{ $member['bio'] }}
                    </p>
                @endif
            </div>
        @empty
            <div class="col-span-full py-12 text-center">
                <p style="color: var(--mosaic-on-surface-variant);">No team members configured</p>
            </div>
        @endforelse
    </div>

    {{-- Admin Hint --}}
    @if($customizable && auth()->check())
        <div class="mt-12 pt-8 max-w-full text-center" style="border-top: 1px solid var(--mosaic-outline-variant); opacity: 0.6;">
            <span class="mosaic-text-label text-xs">
                ✨ Customize: Add team members, change layout
            </span>
        </div>
    @endif
</section>

<style scoped>
    .grid { display: grid; }
    .gap-6 { gap: 1.5rem; }

    .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .md\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .lg\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .lg\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }

    .max-w-2xl { max-width: 42rem; }
    .mx-auto { margin-left: auto; margin-right: auto; }
    .max-w-full { max-width: 100%; }

    .py-12 { padding-top: 3rem; padding-bottom: 3rem; }
    .py-16 { padding-top: 4rem; padding-bottom: 4rem; }
    .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
    .px-12 { padding-left: 3rem; padding-right: 3rem; }

    .mb-12 { margin-bottom: 3rem; }
    .mb-4 { margin-bottom: 1rem; }
    .mb-3 { margin-bottom: 0.75rem; }
    .mb-1 { margin-bottom: 0.25rem; }
    .mt-12 { margin-top: 3rem; }
    .pt-8 { padding-top: 2rem; }

    .text-center { text-align: center; }
    .text-left { text-align: left; }

    .text-3xl { font-size: 1.875rem; }
    .text-4xl { font-size: 2.25rem; }
    .text-5xl { font-size: 3rem; }
    .text-lg { font-size: 1.25rem; }
    .text-base { font-size: 1rem; }
    .text-sm { font-size: 0.875rem; }
    .text-xs { font-size: 0.75rem; }

    .font-bold { font-weight: 700; }
    .font-semibold { font-weight: 600; }

    .leading-relaxed { line-height: 1.625; }

    .w-20 { width: 5rem; }
    .h-20 { height: 5rem; }

    .rounded-full { border-radius: 9999px; }

    .flex { display: flex; }
    .items-center { align-items: center; }
    .justify-center { justify-content: center; }

    .overflow-hidden { overflow: hidden; }

    .col-span-full { grid-column: 1 / -1; }

    @media (max-width: 768px) {
        .md\:text-4xl { font-size: 2.25rem; }
        .md\:py-16 { padding-top: 4rem; padding-bottom: 4rem; }
        .md\:px-12 { padding-left: 3rem; padding-right: 3rem; }
    }
</style>
