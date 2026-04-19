@props([
    'title' => 'Words from the people we work for',
    'testimonials' => [],
])

<section aria-labelledby="testimonials-title"
         class="bg-[var(--color-surface-dark)] py-[var(--section-y,5rem)] text-[var(--color-surface-dark-fg)]">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <h2 id="testimonials-title" class="sr-only">{{ $title }}</h2>

        @forelse ($testimonials as $i => $testimonial)
            <figure class="mx-auto max-w-3xl text-center {{ $i > 0 ? 'mt-20 border-t border-white/10 pt-20' : '' }}">
                <blockquote>
                    <p class="agency-display text-3xl leading-tight sm:text-4xl md:text-5xl">
                        &ldquo;{{ $testimonial['quote'] ?? '' }}&rdquo;
                    </p>
                </blockquote>
                <figcaption class="mt-8 flex items-center justify-center gap-4">
                    @if (! empty($testimonial['avatar']))
                        <img src="{{ $testimonial['avatar'] }}"
                             alt=""
                             loading="lazy"
                             class="h-12 w-12 rounded-full object-cover" />
                    @else
                        <span aria-hidden="true"
                              class="flex h-12 w-12 items-center justify-center rounded-full bg-[var(--color-primary)] text-sm font-bold text-[var(--color-primary-foreground)]">
                            {{ strtoupper(substr($testimonial['name'] ?? '?', 0, 1)) }}
                        </span>
                    @endif
                    <div class="text-left">
                        <p class="font-semibold">{{ $testimonial['name'] ?? '' }}</p>
                        @if (! empty($testimonial['role']))
                            <p class="text-sm text-[var(--color-surface-dark-fg)]/70">{{ $testimonial['role'] }}</p>
                        @endif
                    </div>
                </figcaption>
            </figure>
        @empty
            <p class="text-center text-[var(--color-surface-dark-fg)]/70">
                {{ $slot ?? 'Kind words, coming soon.' }}
            </p>
        @endforelse
    </div>
</section>
