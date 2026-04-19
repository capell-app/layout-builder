@props([
    'title' => 'Meet the team',
    'subtitle' => null,
    'members' => [],
])

<section id="team"
         aria-labelledby="team-title"
         class="bg-[var(--color-bg-muted)] py-[var(--section-y,4rem)]">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <header class="mx-auto mb-12 max-w-2xl text-center">
            <h2 id="team-title" class="text-3xl font-bold sm:text-4xl">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-4 text-[var(--color-fg-muted)]">{{ $subtitle }}</p>
            @endif
        </header>

        <ul role="list" class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($members as $member)
                <li class="rounded-lg border border-[var(--color-border)] bg-[var(--color-bg)] p-6 text-center">
                    @if (! empty($member['photo']))
                        <img src="{{ $member['photo'] }}"
                             alt="Portrait of {{ $member['name'] ?? '' }}"
                             loading="lazy"
                             class="mx-auto mb-4 h-24 w-24 rounded-full object-cover" />
                    @else
                        <div class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-[var(--color-primary)]/10 text-2xl font-bold text-[var(--color-primary)]"
                             aria-hidden="true">
                            {{ strtoupper(substr($member['name'] ?? '?', 0, 1)) }}
                        </div>
                    @endif
                    <h3 class="text-lg font-semibold">{{ $member['name'] ?? '' }}</h3>
                    <p class="text-sm text-[var(--color-accent)]">{{ $member['role'] ?? '' }}</p>
                    @if (! empty($member['bio']))
                        <p class="mt-3 text-sm text-[var(--color-fg-muted)]">{{ $member['bio'] }}</p>
                    @endif
                </li>
            @endforeach

            @if (empty($members))
                {{ $slot ?? '' }}
            @endif
        </ul>
    </div>
</section>
