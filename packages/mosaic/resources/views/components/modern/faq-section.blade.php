{{--
  Modern FAQ Section Widget

  Props:
    - title (string): Section heading
    - faqs (array): Array of FAQ objects { question, answer, category? }
    - categories (array): List of category names for filtering
    - customizable (bool): Show admin hints
--}}

@props([
    'title' => 'Frequently Asked Questions',
    'faqs' => [
        [
            'question' => 'How do I get started with Capell?',
            'answer' => 'Start by installing Capell through Composer, then run the setup command. Our documentation will guide you through the entire process.',
            'category' => 'Getting Started',
        ],
        [
            'question' => 'Do I need coding knowledge?',
            'answer' => 'No! Capell is designed for content editors without technical knowledge. Use the intuitive admin panel to manage your content.',
            'category' => 'Getting Started',
        ],
        [
            'question' => 'Can I customize the design?',
            'answer' => 'Absolutely. Capell provides a complete design system with tokens for colors, typography, and spacing. Customize everything to match your brand.',
            'category' => 'Features',
        ],
        [
            'question' => 'Is there a free trial?',
            'answer' => 'Yes! Sign up for our free tier and explore all core features. Upgrade anytime to unlock advanced capabilities.',
            'category' => 'Pricing',
        ],
    ],
    'categories' => ['Getting Started', 'Features', 'Pricing'],
    'customizable' => true,
])

@php
    $hasCategories = count($categories ?? []) > 0;
@endphp

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

    {{-- Category Tabs --}}
    @if($hasCategories)
        <div class="mb-8 flex justify-center gap-2 flex-wrap max-w-3xl mx-auto">
            <button
                class="faq-category-tab font-semibold px-4 py-2 rounded-full transition-all"
                data-category="all"
                style="
                    background-color: var(--mosaic-primary);
                    color: white;
                    border: none;
                    cursor: pointer;
                "
                onclick="filterFaqCategory(this, 'all')"
            >
                All
            </button>

            @foreach($categories as $category)
                <button
                    class="faq-category-tab font-semibold px-4 py-2 rounded-full transition-all"
                    data-category="{{ $category }}"
                    style="
                        background-color: var(--mosaic-surface-container);
                        color: var(--mosaic-on-surface);
                        border: 1px solid var(--mosaic-outline);
                        cursor: pointer;
                    "
                    onclick="filterFaqCategory(this, '{{ $category }}')"
                >
                    {{ $category }}
                </button>
            @endforeach
        </div>
    @endif

    {{-- FAQ List --}}
    <div class="max-w-3xl mx-auto space-y-3 faq-container">
        @forelse($faqs as $index => $faq)
            <details
                class="mosaic-card faq-item"
                data-category="{{ $faq['category'] ?? 'uncategorized' }}"
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
                ✨ Customize: Add FAQs, categories, questions and answers
            </span>
        </div>
    @endif
</section>

<script>
    function filterFaqCategory(button, category) {
        const tabs = document.querySelectorAll('.faq-category-tab');
        const items = document.querySelectorAll('.faq-item');

        tabs.forEach((tab) => {
            if (tab.getAttribute('data-category') === category) {
                tab.style.backgroundColor = 'var(--mosaic-primary)';
                tab.style.color = 'white';
                tab.style.borderColor = 'transparent';
            } else {
                tab.style.backgroundColor = 'var(--mosaic-surface-container)';
                tab.style.color = 'var(--mosaic-on-surface)';
                tab.style.borderColor = 'var(--mosaic-outline)';
            }
        });

        items.forEach((item) => {
            const itemCategory = item.getAttribute('data-category');
            if (category === 'all' || itemCategory === category) {
                item.style.display = 'block';
                item.style.animation = 'fadeIn 0.3s ease-out';
            } else {
                item.style.display = 'none';
            }
        });
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
</script>

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

    {{-- Category Tabs Styling --}}
    .faq-category-tab {
        transition: all 0.2s ease;
    }

    .faq-category-tab:hover {
        transform: translateY(-2px);
    }

    {{-- FAQ Item Animation --}}
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    .faq-item {
        animation: fadeIn 0.3s ease-out;
    }

    @media (max-width: 768px) {
        .md\:text-4xl { font-size: 2.25rem; }
        .md\:py-16 { padding-top: 4rem; padding-bottom: 4rem; }
        .md\:px-12 { padding-left: 3rem; padding-right: 3rem; }
    }
</style>
