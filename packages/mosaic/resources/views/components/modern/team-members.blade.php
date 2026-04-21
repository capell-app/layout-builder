{{--
    Modern Team Members Widget
    
    Props:
    - title (string): Section heading
    - members (array): Array of team member objects
    Each member: { name, role, avatar, bio, tags[], social[] }
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
            'tags' => ['Design', 'Leadership'],
            'social' => ['twitter' => 'https://twitter.com', 'linkedin' => 'https://linkedin.com'],
        ],
        [
            'name' => 'Emma Davis',
            'role' => 'Engineering Manager',
            'avatar' => '👩‍🔬',
            'bio' => 'Full-stack developer and architect',
            'tags' => ['Engineering', 'Architecture'],
            'social' => ['github' => 'https://github.com', 'linkedin' => 'https://linkedin.com'],
        ],
        [
            'name' => 'James Wilson',
            'role' => 'CEO & Co-founder',
            'avatar' => '🧑‍💼',
            'bio' => 'Serial entrepreneur and visionary',
            'tags' => ['Strategy', 'Leadership'],
            'social' => ['twitter' => 'https://twitter.com', 'linkedin' => 'https://linkedin.com'],
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

<section class="px-6 py-12 md:px-12 md:py-16">
    @if ($title)
        <div class="mx-auto mb-12 max-w-2xl text-center">
            <h2 class="mb-4 text-3xl font-bold text-gray-900 md:text-4xl">
                {{ $title }}
            </h2>
        </div>
    @endif

    <div class="{{ $gridClass }} grid gap-6">
        @forelse ($members as $member)
            <div class="rounded-2xl bg-gray-50 p-6 text-center">
                @if (isset($member['avatar']))
                    <div
                        class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-gray-100 text-5xl"
                    >
                        {{ $member['avatar'] }}
                    </div>
                @endif

                @if (isset($member['name']))
                    <h3 class="mb-1 text-lg font-bold text-gray-900">
                        {{ $member['name'] }}
                    </h3>
                @endif

                @if (isset($member['role']))
                    <p
                        class="mb-3 text-sm font-semibold uppercase tracking-wide text-indigo-600"
                    >
                        {{ $member['role'] }}
                    </p>
                @endif

                @if (isset($member['bio']))
                    <p class="mb-4 text-sm leading-relaxed text-gray-500">
                        {{ $member['bio'] }}
                    </p>
                @endif

                @if (isset($member['tags']) && count($member['tags']) > 0)
                    <div class="mb-4 flex flex-wrap justify-center gap-2">
                        @foreach ($member['tags'] as $tag)
                            <span
                                class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700"
                            >
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>
                @endif

                @if (isset($member['social']) && count($member['social']) > 0)
                    @php
                        $socialIcons = [
                            'twitter' => '𝕏',
                            'linkedin' => 'in',
                            'github' => '🐙',
                            'website' => '🌐',
                            'email' => '✉',
                        ];
                    @endphp

                    <div
                        class="flex justify-center gap-3 border-t border-gray-200 pt-4"
                    >
                        @foreach ($member['social'] as $platform => $url)
                            @if ($url && isset($socialIcons[$platform]))
                                <a
                                    href="{{ $url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    title="{{ ucfirst($platform) }}"
                                    class="font-semibold text-gray-400 transition-colors hover:text-indigo-600"
                                >
                                    {{ $socialIcons[$platform] }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="col-span-full py-12 text-center">
                <p class="text-gray-500">No team members configured</p>
            </div>
        @endforelse
    </div>

    @if ($customizable && auth()->check())
        <div class="mt-12 border-t border-gray-100 pt-8 text-center opacity-60">
            <span class="text-xs text-gray-500">
                ✨ Customize: Add members, tags, social links, change layout
            </span>
        </div>
    @endif
</section>
