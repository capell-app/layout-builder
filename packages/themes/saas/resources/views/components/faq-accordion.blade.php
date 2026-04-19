@props([
    'title' => 'Frequently asked questions',
    'subtitle' => null,
    'faqs' => [],
])

<section id="faq"
         aria-labelledby="faq-title"
         class="bg-[var(--color-bg-muted)] py-[var(--section-y,5rem)]">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <header class="mb-10 text-center">
            <h2 id="faq-title" class="text-3xl font-bold tracking-tight sm:text-4xl">
                {{ $title }}
            </h2>
            @if ($subtitle)
                <p class="mt-4 text-[var(--color-fg-muted)]">{{ $subtitle }}</p>
            @endif
        </header>

        <div class="rounded-xl border border-[var(--color-border)] bg-[var(--color-bg)] px-6 py-2 shadow-[var(--shadow-card)]">
            @forelse ($faqs as $faq)
                <details class="faq-item">
                    <summary>
                        <span>{{ $faq['question'] ?? '' }}</span>
                    </summary>
                    <div class="faq-answer">{{ $faq['answer'] ?? '' }}</div>
                </details>
            @empty
                <p class="py-4 text-center text-[var(--color-fg-muted)]">No FAQs configured.</p>
            @endforelse
        </div>
    </div>
</section>
