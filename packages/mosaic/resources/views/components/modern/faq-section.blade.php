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

<style>
    @keyframes faqFadeIn {
        from {
            opacity: 0;
            transform: translateY(-4px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<section class="px-6 py-12 md:px-12 md:py-16">
    @if ($title)
        <div class="mx-auto mb-12 max-w-2xl text-center">
            <h2 class="text-3xl font-bold text-gray-900 md:text-4xl">
                {{ $title }}
            </h2>
        </div>
    @endif

    @if ($hasCategories)
        <div class="mx-auto mb-8 flex max-w-3xl flex-wrap justify-center gap-2">
            <button
                class="faq-category-tab rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-all"
                data-category="all"
                onclick="filterFaqCategory(this, 'all')"
            >
                All
            </button>

            @foreach ($categories as $category)
                <button
                    class="faq-category-tab rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 transition-all hover:border-indigo-300 hover:text-indigo-600"
                    data-category="{{ $category }}"
                    onclick="filterFaqCategory(this, '{{ $category }}')"
                >
                    {{ $category }}
                </button>
            @endforeach
        </div>
    @endif

    <div class="faq-container mx-auto max-w-3xl space-y-3">
        @forelse ($faqs as $faq)
            <details
                class="faq-item group rounded-xl border border-gray-100 bg-gray-50"
                data-category="{{ $faq['category'] ?? 'uncategorized' }}"
            >
                <summary
                    class="flex cursor-pointer select-none items-center justify-between p-5 text-base font-semibold text-gray-900"
                >
                    <span>{{ $faq['question'] }}</span>
                    <span
                        class="ml-4 flex-shrink-0 text-xl text-indigo-500 transition-transform group-open:rotate-45"
                    >
                        +
                    </span>
                </summary>

                <div
                    class="border-t border-gray-100 px-5 pb-5 pt-4 leading-relaxed text-gray-600"
                >
                    {{ $faq['answer'] }}
                </div>
            </details>
        @empty
            <div class="py-12 text-center">
                <p class="text-gray-500">No FAQs configured</p>
            </div>
        @endforelse
    </div>

    @if ($customizable && auth()->check())
        <div class="mt-12 border-t border-gray-100 pt-8 text-center opacity-60">
            <span class="text-xs text-gray-500">
                ✨ Customize: Add FAQs, categories, questions and answers
            </span>
        </div>
    @endif
</section>

<script>
    function filterFaqCategory(button, category) {
        document.querySelectorAll('.faq-category-tab').forEach((tab) => {
            const isActive = tab.getAttribute('data-category') === category
            tab.className = isActive
                ? 'faq-category-tab rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-all'
                : 'faq-category-tab rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 transition-all hover:border-indigo-300 hover:text-indigo-600'
        })

        document.querySelectorAll('.faq-item').forEach((item) => {
            const matches =
                category === 'all' ||
                item.getAttribute('data-category') === category
            item.style.display = matches ? 'block' : 'none'
            if (matches) item.style.animation = 'faqFadeIn 0.25s ease-out'
        })
    }
</script>
