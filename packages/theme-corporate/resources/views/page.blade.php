@once
    <style>
        [data-theme-studio-theme='corporate'] {
            background: #f8fafc;
            color: #0f172a;
            font-family: var(--theme-body-font, Inter, system-ui, sans-serif);
        }

        [data-theme-studio-theme='corporate'] section {
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: clamp(4rem, 7vw, 7rem) 1.5rem;
        }

        [data-theme-studio-theme='corporate'] section > div {
            max-width: 72rem;
            margin-inline: auto;
        }

        [data-theme-studio-theme='corporate'] h1,
        [data-theme-studio-theme='corporate'] h2 {
            max-width: 16ch;
            color: #0f172a;
            font-family: var(--theme-heading-font, inherit);
            font-size: clamp(2.75rem, 6vw, 5.25rem);
            font-weight: 700;
            line-height: 1;
            letter-spacing: 0;
        }

        [data-theme-studio-theme='corporate'] p {
            max-width: 44rem;
            color: #475569;
            font-size: 1.125rem;
            line-height: 1.8;
        }

        [data-theme-studio-theme='corporate'] a {
            color: var(--theme-primary, #1a2d6d);
        }

        [data-theme-studio-theme='corporate'] img,
        [data-theme-studio-theme='corporate'] section div:empty {
            border-radius: 0.5rem;
        }
    </style>
@endonce

<div
    data-theme-studio-theme="{{ $themeKey }}"
    style="{{ collect($brand->tokens())->map(fn ($value, $token) => $token . ':' . $value)->implode(';') }}"
    class="min-h-screen bg-slate-50 text-slate-950 antialiased"
>
    {!! $content !!}
</div>
