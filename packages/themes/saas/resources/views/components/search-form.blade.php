@props([
    'action' => '/search',
    'placeholder' => 'Search docs, articles, integrations…',
    'label' => 'Search the site',
])

<form
    role="search"
    action="{{ $action }}"
    method="GET"
    class="w-full max-w-lg"
>
    <label
        for="saas-search"
        class="sr-only"
    >
        {{ $label }}
    </label>
    <div
        class="flex items-center gap-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-bg)] px-3 py-2 focus-within:border-[var(--color-primary)]"
    >
        <span
            aria-hidden="true"
            class="text-[var(--color-fg-subtle)]"
        >
            &#128269;
        </span>
        <input
            id="saas-search"
            name="q"
            type="search"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            class="block w-full border-0 bg-transparent p-0 text-sm text-[var(--color-fg)] placeholder:text-[var(--color-fg-subtle)] focus:outline-none focus:ring-0"
        />
        <button
            type="submit"
            class="shrink-0 rounded-md bg-[var(--color-primary)] px-3 py-1 text-xs font-semibold text-[var(--color-primary-foreground)]"
        >
            {{ $slot->isEmpty() ? 'Search' : $slot }}
        </button>
    </div>
</form>
