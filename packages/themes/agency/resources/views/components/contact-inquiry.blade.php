@props([
    'title' => 'Start a project',
    'subtitle' => null,
    'action' => '/inquiry',
    'method' => 'POST',
    'submitLabel' => 'Send inquiry',
    'budgetOptions' => [
        ['value' => 'under-25k', 'label' => 'Under $25k'],
        ['value' => '25k-75k', 'label' => '$25k – $75k'],
        ['value' => '75k-200k', 'label' => '$75k – $200k'],
        ['value' => '200k-plus', 'label' => '$200k+'],
    ],
    'timelineOptions' => [
        ['value' => 'urgent', 'label' => 'ASAP (under 4 weeks)'],
        ['value' => 'quarter', 'label' => 'This quarter'],
        ['value' => 'half', 'label' => 'Next 6 months'],
        ['value' => 'flexible', 'label' => 'Flexible'],
    ],
])

<section
    id="inquiry"
    aria-labelledby="inquiry-title"
    class="bg-[var(--color-bg-muted)] py-[var(--section-y,5rem)]"
>
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <header class="mb-10 text-center">
            <h2
                id="inquiry-title"
                class="agency-display text-4xl sm:text-5xl md:text-6xl"
            >
                {{ $title }}
            </h2>
            @if ($subtitle)
                <p class="mt-4 text-lg text-[var(--color-fg-muted)]">
                    {{ $subtitle }}
                </p>
            @endif
        </header>

        <form
            action="{{ $action }}"
            method="{{ strtoupper($method) }}"
            class="space-y-6 rounded-[var(--radius-lg)] border border-[var(--color-border)] bg-[var(--color-bg)] p-6 sm:p-10"
            aria-describedby="inquiry-title"
            novalidate
        >
            @if (function_exists('csrf_field'))
                {!! csrf_field() !!}
            @endif

            {{-- Honeypot --}}
            <div
                class="absolute h-0 w-0 overflow-hidden"
                aria-hidden="true"
            >
                <label for="agency-website">Website (leave blank)</label>
                <input
                    type="text"
                    id="agency-website"
                    name="website"
                    tabindex="-1"
                    autocomplete="off"
                />
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label
                        for="agency-name"
                        class="block text-sm font-semibold"
                    >
                        Name
                    </label>
                    <input
                        id="agency-name"
                        name="name"
                        type="text"
                        required
                        autocomplete="name"
                        class="mt-2 block w-full rounded-md border border-[var(--color-border)] bg-[var(--color-bg)] px-3 py-2.5 text-[var(--color-fg)] shadow-sm"
                    />
                </div>
                <div>
                    <label
                        for="agency-email"
                        class="block text-sm font-semibold"
                    >
                        Email
                    </label>
                    <input
                        id="agency-email"
                        name="email"
                        type="email"
                        required
                        autocomplete="email"
                        class="mt-2 block w-full rounded-md border border-[var(--color-border)] bg-[var(--color-bg)] px-3 py-2.5 text-[var(--color-fg)] shadow-sm"
                    />
                </div>
            </div>

            <div>
                <label
                    for="agency-company"
                    class="block text-sm font-semibold"
                >
                    Company
                    <span class="font-normal text-[var(--color-fg-muted)]">
                        (optional)
                    </span>
                </label>
                <input
                    id="agency-company"
                    name="company"
                    type="text"
                    autocomplete="organization"
                    class="mt-2 block w-full rounded-md border border-[var(--color-border)] bg-[var(--color-bg)] px-3 py-2.5 text-[var(--color-fg)] shadow-sm"
                />
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label
                        for="agency-budget"
                        class="block text-sm font-semibold"
                    >
                        Budget
                    </label>
                    <select
                        id="agency-budget"
                        name="budget"
                        class="mt-2 block w-full rounded-md border border-[var(--color-border)] bg-[var(--color-bg)] px-3 py-2.5 text-[var(--color-fg)] shadow-sm"
                    >
                        <option value="">Select a range…</option>
                        @foreach ($budgetOptions as $option)
                            <option value="{{ $option['value'] ?? '' }}">
                                {{ $option['label'] ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label
                        for="agency-timeline"
                        class="block text-sm font-semibold"
                    >
                        Timeline
                    </label>
                    <select
                        id="agency-timeline"
                        name="timeline"
                        class="mt-2 block w-full rounded-md border border-[var(--color-border)] bg-[var(--color-bg)] px-3 py-2.5 text-[var(--color-fg)] shadow-sm"
                    >
                        <option value="">Pick one…</option>
                        @foreach ($timelineOptions as $option)
                            <option value="{{ $option['value'] ?? '' }}">
                                {{ $option['label'] ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label
                    for="agency-message"
                    class="block text-sm font-semibold"
                >
                    Tell us about your project
                </label>
                <textarea
                    id="agency-message"
                    name="message"
                    rows="6"
                    required
                    class="mt-2 block w-full rounded-md border border-[var(--color-border)] bg-[var(--color-bg)] px-3 py-2.5 text-[var(--color-fg)] shadow-sm"
                ></textarea>
            </div>

            {{ $slot ?? '' }}

            <div class="pt-2">
                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-[var(--color-primary)] px-8 py-4 text-base font-semibold text-[var(--color-primary-foreground)] shadow-[var(--shadow-float)] transition hover:brightness-110 sm:w-auto"
                >
                    {{ $submitLabel }}
                    <span aria-hidden="true">&rarr;</span>
                </button>
            </div>
        </form>
    </div>
</section>
