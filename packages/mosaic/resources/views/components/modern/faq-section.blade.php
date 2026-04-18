{{--
  Modern FAQ Section Widget

  Props:
    - title (string): Section heading
    - faqs (array): Array of FAQ objects { question, answer }
    - customizable (bool): Show admin hints
--}}

@props([
    'title' => 'Frequently Asked Questions',
    'faqs' => [
        [
            'question' => 'How do I get started with Capell?',
            'answer' => 'Start by installing Capell through Composer, then run the setup command. Our documentation will guide you through the entire process.',
        ],
        [
            'question' => 'Do I need coding knowledge?',
            'answer' => 'No! Capell is designed for content editors without technical knowledge. Use the intuitive admin panel to manage your content.',
        ],
        [
            'question' => 'Can I customize the design?',
            'answer' => 'Absolutely. Capell provides a complete design system with tokens for colors, typography, and spacing. Customize everything to match your brand.',
        ],
        [
            'question' => 'Is there a free trial?',
            'answer' => 'Yes! Sign up for our free tier and explore all core features. Upgrade anytime to unlock advanced capabilities.',
        ],
    ],
    'customizable' => true,
])

<section class="mosaic-faq py-12 md:py-16 px-6 md:px-12">
    {{-- Header --}}
    @if($title)
        <div class="mb-12 text-center max-w-2xl mx-auto">
            <h2
                class="text-3xl md:text-4xl font-bold"
                style="
                    color: var(--mosaic-on-surface);
                    font-family: var(--mosaic-font-headline);
                "
            >
                {{ $title }}
            </h2>
        </div>
    @endif

    {{-- FAQ List --}}
    <div class="max-w-3xl mx-auto space-y-3">
        @forelse($faqs as $index => $faq)
            <details
                class="mosaic-card"
                style="background-color: var(--mosaic-surface-container);"
            >
                <summary
                    class="cursor-pointer select-none font-bold text-lg p-4 flex items-center justify-between"
                    style="color: var(--mosaic-on-surface);"
                >
                    <span>{{ $faq['question'] }}</span>
                    <span class="text-xl" style="color: var(--mosaic-tertiary); transition: transform 0.2s;">
                        +
                    </span>
                </summary>

                <div
                    class="px-4 pb-4"
                    style="border-top: 1px solid var(--mosaic-outline-variant); color: var(--mosaic-on-surface-variant); line-height: 1.625;"
                >
                    {{ $faq['answer'] }}
                </div>
            </details>
        @empty
            <div class="py-12 text-center">
                <p style="color: var(--mosaic-on-surface-variant);">No FAQs configured</p>
            </div>
        @endforelse
    </div>

    {{-- Admin Hint --}}
    @if($customizable && auth()->check())
        <div class="mt-12 pt-8 max-w-full text-center" style="border-top: 1px solid var(--mosaic-outline-variant); opacity: 0.6;">
            <span class="mosaic-text-label text-xs">
                ✨ Customize: Add FAQs, edit questions and answers
            </span>
        </div>
    @endif
</section>

<style scoped>
    .space-y-3 > * + * { margin-top: 0.75rem; }

    .max-w-2xl { max-width: 42rem; }
    .max-w-3xl { max-width: 48rem; }
    .max-w-full { max-width: 100%; }
    .mx-auto { margin-left: auto; margin-right: auto; }

    .py-12 { padding-top: 3rem; padding-bottom: 3rem; }
    .py-16 { padding-top: 4rem; padding-bottom: 4rem; }
    .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
    .px-12 { padding-left: 3rem; padding-right: 3rem; }
    .px-4 { padding-left: 1rem; padding-right: 1rem; }
    .pb-4 { padding-bottom: 1rem; }
    .p-4 { padding: 1rem; }

    .mb-12 { margin-bottom: 3rem; }
    .mt-12 { margin-top: 3rem; }
    .pt-8 { padding-top: 2rem; }

    .text-center { text-align: center; }

    .text-3xl { font-size: 1.875rem; }
    .text-4xl { font-size: 2.25rem; }
    .text-lg { font-size: 1.125rem; }
    .text-xl { font-size: 1.25rem; }
    .text-xs { font-size: 0.75rem; }

    .font-bold { font-weight: 700; }

    .flex { display: flex; }
    .items-center { align-items: center; }
    .justify-between { justify-content: space-between; }

    .cursor-pointer { cursor: pointer; }
    .select-none { user-select: none; }

    details > summary::-webkit-details-marker { display: none; }

    details[open] summary span {
        transform: rotate(45deg);
    }

    @media (max-width: 768px) {
        .md\:text-4xl { font-size: 2.25rem; }
        .md\:py-16 { padding-top: 4rem; padding-bottom: 4rem; }
        .md\:px-12 { padding-left: 3rem; padding-right: 3rem; }
    }
</style>
