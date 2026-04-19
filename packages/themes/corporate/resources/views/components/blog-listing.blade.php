@props([
    'title' => 'From our blog',
    'subtitle' => null,
    'posts' => [],
])

<section
    id="blog"
    aria-labelledby="blog-title"
    class="bg-[var(--color-bg-muted)] py-[var(--section-y,4rem)]"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <header class="mx-auto mb-12 max-w-2xl text-center">
            <h2
                id="blog-title"
                class="text-3xl font-bold sm:text-4xl"
            >
                {{ $title }}
            </h2>
            @if ($subtitle)
                <p class="mt-4 text-[var(--color-fg-muted)]">
                    {{ $subtitle }}
                </p>
            @endif
        </header>

        <ul
            role="list"
            class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3"
        >
            @forelse ($posts as $post)
                <li>
                    <article
                        class="h-full overflow-hidden rounded-lg border border-[var(--color-border)] bg-[var(--color-bg)] shadow-sm transition hover:shadow-md"
                    >
                        @if (! empty($post['image']))
                            <img
                                src="{{ $post['image'] }}"
                                alt=""
                                loading="lazy"
                                class="h-48 w-full object-cover"
                            />
                        @endif

                        <div class="p-6">
                            @if (! empty($post['category']))
                                <p
                                    class="text-xs font-semibold uppercase tracking-widest text-[var(--color-accent)]"
                                >
                                    {{ $post['category'] }}
                                </p>
                            @endif

                            <h3 class="mt-2 text-xl font-semibold leading-snug">
                                <a
                                    href="{{ $post['url'] ?? '#' }}"
                                    class="hover:text-[var(--color-primary)]"
                                >
                                    {{ $post['title'] ?? '' }}
                                </a>
                            </h3>
                            @if (! empty($post['excerpt']))
                                <p
                                    class="mt-3 text-sm text-[var(--color-fg-muted)]"
                                >
                                    {{ $post['excerpt'] }}
                                </p>
                            @endif

                            @if (! empty($post['published_at']))
                                <time
                                    datetime="{{ $post['published_at'] }}"
                                    class="mt-4 block text-xs text-[var(--color-fg-muted)]"
                                >
                                    {{ $post['published_at'] }}
                                </time>
                            @endif
                        </div>
                    </article>
                </li>
            @empty
                <li
                    class="col-span-full text-center text-[var(--color-fg-muted)]"
                >
                    {{ $slot ?? 'No posts yet.' }}
                </li>
            @endforelse
        </ul>
    </div>
</section>
