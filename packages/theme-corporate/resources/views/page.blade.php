@once
    <style>
        [data-capell-theme='corporate'] {
            background: #f7f8f6;
            color: #0f172a;
            font-family: var(--theme-body-font, Inter, system-ui, sans-serif);
        }

        [data-capell-theme='corporate'] ::selection {
            background: color-mix(
                in srgb,
                var(--theme-accent, #f59e0b) 28%,
                transparent
            );
        }

        [data-capell-theme='corporate'] a {
            color: inherit;
        }

        [data-capell-theme='corporate'] h1,
        [data-capell-theme='corporate'] h2,
        [data-capell-theme='corporate'] h3 {
            font-family: var(--theme-heading-font, inherit);
            letter-spacing: 0;
        }

        [data-capell-theme='corporate'] img {
            background: #e5e7eb;
        }
    </style>
@endonce

<div
    data-capell-theme="{{ $themeKey }}"
    style="{{ collect($brand->tokens())->map(fn ($value, $token) => $token . ':' . $value)->implode(';') }}"
    class="min-h-screen bg-[#f7f8f6] text-slate-950 antialiased"
>
    {!! $content !!}
</div>
