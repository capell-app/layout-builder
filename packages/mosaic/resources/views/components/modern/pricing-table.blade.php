{{--
    Modern Pricing Table Widget
    
    Props:
    - title (string): Section heading
    - plans (array): Array of pricing plan objects
    - currency (string): Currency symbol - Default: '$'
    - billingOptions (string): 'monthly|annual|both' - Default: 'monthly'
    - customizable (bool): Show admin hints
--}}

@props([
    'title' => 'Simple, Transparent Pricing',
    'plans' => [
        [
            'name' => 'Starter',
            'price' => '29',
            'priceAnnual' => '290',
            'description' => 'For individuals',
            'features' => ['Up to 5 projects', '1 GB storage', 'Email support'],
            'cta' => ['label' => 'Get Started', 'url' => '#'],
            'featured' => false,
        ],
        [
            'name' => 'Professional',
            'price' => '79',
            'priceAnnual' => '790',
            'description' => 'For teams',
            'features' => ['Unlimited projects', '100 GB storage', 'Priority support', 'Advanced analytics'],
            'cta' => ['label' => 'Start Free', 'url' => '#'],
            'featured' => true,
        ],
        [
            'name' => 'Enterprise',
            'price' => 'Custom',
            'priceAnnual' => 'Custom',
            'description' => 'For enterprises',
            'features' => ['Everything in Pro', 'Unlimited storage', 'Dedicated support', 'Custom integrations'],
            'cta' => ['label' => 'Contact Sales', 'url' => '#'],
            'featured' => false,
        ],
    ],
    'currency' => '$',
    'billingOptions' => 'monthly',
    'customizable' => true,
])

<section class="px-6 py-12 md:px-12 md:py-16">
    @if ($title)
        <div class="mx-auto mb-12 max-w-2xl text-center">
            <h2 class="text-3xl font-bold text-gray-900 md:text-4xl">
                {{ $title }}
            </h2>
        </div>
    @endif

    @if ($billingOptions === 'both')
        <div
            class="mb-12 flex items-center justify-center gap-4"
        >
            <span
                class="text-gray-700"
            >
                Monthly
            </span>
            <button
                class="billing-toggle-button relative h-8 w-14 rounded-full bg-indigo-600 transition-colors"
                onclick="
                    toggleBillingCycle(this)
                "
            >
                <div
                    class="billing-toggle-dot absolute left-1 top-1 h-6 w-6 rounded-full bg-white transition-all"
                ></div>
            </button>
            <span
                class="text-gray-700"
            >
                Annual
            </span>
            <span
                class="text-sm font-semibold text-indigo-600"
            >
                Save
                17%
            </span>
        </div>
    @endif

    <div
        class="pricing-grid mx-auto grid max-w-5xl grid-cols-1 gap-6 md:grid-cols-3"
        data-billing="{{ $billingOptions === 'both' ? 'monthly' : $billingOptions }}"
    >
        @forelse ($plans as $plan)
            @if ($plan['featured'] ?? false)
                <div
                    class="pricing-plan relative rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-800 p-8 text-white shadow-xl md:-my-4"
                    data-price-monthly="{{ $plan['price'] ?? '' }}"
                    data-price-annual="{{ $plan['priceAnnual'] ?? $plan['price'] ?? '' }}"
                >
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="rounded-full bg-amber-400 px-4 py-1 text-xs font-bold text-amber-900">
                            Most Popular
                        </span>
                    </div>

                    <h3 class="mb-1 text-2xl font-bold text-white">{{ $plan['name'] }}</h3>

                    @if (isset($plan['description']))
                        <p class="mb-6 text-sm text-indigo-200">{{ $plan['description'] }}</p>
                    @endif

                    <div class="price-container mb-6">
                        <span class="plan-price text-4xl font-bold text-white">{{ $currency }}{{ $plan['price'] }}</span>
                        @if ($plan['price'] !== 'Custom')
                            <span class="billing-period text-indigo-200">/month</span>
                        @endif
                    </div>

                    @if (isset($plan['features']))
                        <ul class="mb-8 space-y-3">
                            @foreach ($plan['features'] as $feature)
                                <li class="flex items-center gap-2 text-indigo-100">
                                    <span class="font-bold text-indigo-300">✓</span>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if (isset($plan['cta']))
                        <a
                            href="{{ $plan['cta']['url'] }}"
                            class="block w-full rounded-lg bg-white px-6 py-3 text-center font-semibold text-indigo-700 transition-opacity hover:opacity-90"
                        >
                            {{ $plan['cta']['label'] }}
                        </a>
                    @endif
                </div>
            @else
                <div
                    class="pricing-plan relative rounded-2xl border border-gray-100 bg-gray-50 p-8"
                    data-price-monthly="{{ $plan['price'] ?? '' }}"
                    data-price-annual="{{ $plan['priceAnnual'] ?? $plan['price'] ?? '' }}"
                >
                    <h3 class="mb-1 text-2xl font-bold text-gray-900">{{ $plan['name'] }}</h3>

                    @if (isset($plan['description']))
                        <p class="mb-6 text-sm text-gray-500">{{ $plan['description'] }}</p>
                    @endif

                    <div class="price-container mb-6">
                        <span class="plan-price text-4xl font-bold text-gray-900">{{ $currency }}{{ $plan['price'] }}</span>
                        @if ($plan['price'] !== 'Custom')
                            <span class="billing-period text-gray-500">/month</span>
                        @endif
                    </div>

                    @if (isset($plan['features']))
                        <ul class="mb-8 space-y-3">
                            @foreach ($plan['features'] as $feature)
                                <li class="flex items-center gap-2 text-gray-700">
                                    <span class="font-bold text-indigo-500">✓</span>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if (isset($plan['cta']))
                        <a
                            href="{{ $plan['cta']['url'] }}"
                            class="block w-full rounded-lg border border-gray-200 bg-white px-6 py-3 text-center font-semibold text-gray-700 transition-colors hover:border-indigo-300 hover:text-indigo-600"
                        >
                            {{ $plan['cta']['label'] }}
                        </a>
                    @endif
                </div>
            @endif
        @empty
            <div class="col-span-full py-12 text-center">
                <p class="text-gray-500">No pricing plans configured</p>
            </div>
        @endforelse
    </div>

    @if ($customizable && auth()->check())
        <div class="mt-12 border-t border-gray-100 pt-8 text-center opacity-60">
            <span class="text-xs text-gray-500">
                ✨ Customize: Add plans, features, pricing, featured plan,
                billing cycle options
            </span>
        </div>
    @endif
</section>

<script>
    function toggleBillingCycle(button) {
        const grid = document.querySelector('.pricing-grid')
        const currentBilling = grid.getAttribute('data-billing')
        const newBilling = currentBilling === 'monthly' ? 'annual' : 'monthly'

        grid.setAttribute('data-billing', newBilling)

        const plans = document.querySelectorAll('.pricing-plan')
        plans.forEach((plan) => {
            const priceElement = plan.querySelector('.plan-price')
            const periodElement = plan.querySelector('.billing-period')

            if (newBilling === 'annual') {
                const annualPrice = plan.getAttribute('data-price-annual')
                priceElement.textContent = priceElement.textContent.replace(
                    plan.getAttribute('data-price-monthly'),
                    annualPrice,
                )
                if (annualPrice !== 'Custom' && periodElement) {
                    periodElement.textContent = '/year'
                }
            } else {
                const monthlyPrice = plan.getAttribute('data-price-monthly')
                priceElement.textContent = priceElement.textContent.replace(
                    plan.getAttribute('data-price-annual'),
                    monthlyPrice,
                )
                if (monthlyPrice !== 'Custom' && periodElement) {
                    periodElement.textContent = '/month'
                }
            }
        })

        const dot = button.querySelector('.billing-toggle-dot')
        dot.style.left = newBilling === 'annual' ? '30px' : '4px'
    }
</script>
