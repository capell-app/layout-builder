@props([
    'label' => 'Toggle dark mode',
])

<button type="button"
        aria-label="{{ $label }}"
        data-saas-dark-toggle
        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[var(--color-border)] bg-[var(--color-bg)] text-[var(--color-fg-muted)] transition hover:text-[var(--color-fg)]">
    <span aria-hidden="true" data-icon-light>&#9728;</span>
    <span aria-hidden="true" data-icon-dark class="hidden">&#9790;</span>
    {{ $slot ?? '' }}
</button>

<script>
    (function () {
        var btns = document.querySelectorAll('[data-saas-dark-toggle]');
        btns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var root = document.documentElement;
                var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-theme', next);
                try { localStorage.setItem('capell-saas-theme', next); } catch (e) {}
            });
        });
        try {
            var saved = localStorage.getItem('capell-saas-theme');
            if (saved) document.documentElement.setAttribute('data-theme', saved);
        } catch (e) {}
    })();
</script>
