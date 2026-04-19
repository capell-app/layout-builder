@props([
    'action' => '/search',
    'placeholder' => 'Search…',
    'label' => 'Search the site',
])

<form
    role="search"
    action="{{ $action }}"
    method="GET"
    class="w-full max-w-md"
>
    <label
        for="corporate-search"
        class="sr-only"
    >
        {{ $label }}
    </label>
    <div class="flex items-center gap-2">
        <input
            id="corporate-search"
            name="q"
            type="search"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            class="block w-full rounded-md border border-[var(--color-border)] bg-[var(--color-bg)] px-3 py-2 text-sm"
        />
        <button
            type="submit"
            class="shrink-0 rounded-md bg-[var(--color-primary)] px-4 py-2 text-sm font-semibold text-[var(--color-primary-foreground)]"
        >
            {{ $slot->isEmpty() ? 'Search' : $slot }}
        </button>
    </div>
</form>
