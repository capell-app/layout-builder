@props([
    'action' => '/search',
    'placeholder' => 'Search the studio…',
    'label' => 'Search the site',
])

<form role="search" action="{{ $action }}" method="GET" class="w-full max-w-md">
    <label for="agency-search" class="sr-only">{{ $label }}</label>
    <div class="flex items-center gap-2">
        <input id="agency-search"
               name="q"
               type="search"
               placeholder="{{ $placeholder }}"
               autocomplete="off"
               class="block w-full rounded-full border border-[var(--color-border)] bg-[var(--color-bg)] px-4 py-2 text-sm" />
        <button type="submit"
                class="shrink-0 rounded-full bg-[var(--color-primary)] px-5 py-2 text-sm font-semibold text-[var(--color-primary-foreground)]">
            {{ $slot->isEmpty() ? 'Search' : $slot }}
        </button>
    </div>
</form>
