@props([
    'title' => 'Contact us',
    'subtitle' => 'Tell us about your project. We\'ll respond within one business day.',
    'action' => '/contact',
    'method' => 'POST',
    'submitLabel' => 'Send message',
])

<section
    id="contact"
    aria-labelledby="contact-title"
    class="bg-[var(--color-bg)] py-[var(--section-y,4rem)]"
>
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
        <header class="mb-10 text-center">
            <h2
                id="contact-title"
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

        <form
            action="{{ $action }}"
            method="{{ strtoupper($method) }}"
            class="space-y-6"
            aria-describedby="contact-title"
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
                <label for="corp-website">Website (leave blank)</label>
                <input
                    type="text"
                    id="corp-website"
                    name="website"
                    tabindex="-1"
                    autocomplete="off"
                />
            </div>

            <div>
                <label
                    for="corp-name"
                    class="block text-sm font-medium"
                >
                    Name
                </label>
                <input
                    id="corp-name"
                    name="name"
                    type="text"
                    required
                    autocomplete="name"
                    class="mt-2 block w-full rounded-md border border-[var(--color-border)] bg-[var(--color-bg)] px-3 py-2 text-[var(--color-fg)] shadow-sm"
                />
            </div>

            <div>
                <label
                    for="corp-email"
                    class="block text-sm font-medium"
                >
                    Email
                </label>
                <input
                    id="corp-email"
                    name="email"
                    type="email"
                    required
                    autocomplete="email"
                    class="mt-2 block w-full rounded-md border border-[var(--color-border)] bg-[var(--color-bg)] px-3 py-2 text-[var(--color-fg)] shadow-sm"
                />
            </div>

            <div>
                <label
                    for="corp-message"
                    class="block text-sm font-medium"
                >
                    Message
                </label>
                <textarea
                    id="corp-message"
                    name="message"
                    rows="5"
                    required
                    class="mt-2 block w-full rounded-md border border-[var(--color-border)] bg-[var(--color-bg)] px-3 py-2 text-[var(--color-fg)] shadow-sm"
                ></textarea>
            </div>

            {{ $slot ?? '' }}

            <div>
                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center rounded-md bg-[var(--color-primary)] px-6 py-3 font-semibold text-[var(--color-primary-foreground)] shadow-sm transition hover:brightness-110 sm:w-auto"
                >
                    {{ $submitLabel }}
                </button>
            </div>
        </form>
    </div>
</section>
