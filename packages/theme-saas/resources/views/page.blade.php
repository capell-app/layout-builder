@once
    <style>
        [data-capell-theme='saas'] {
            background:
                radial-gradient(
                    circle at 20% 0%,
                    color-mix(
                        in srgb,
                        var(--theme-primary, #6366f1) 18%,
                        transparent
                    ),
                    transparent 34rem
                ),
                #fff;
            color: #0f172a;
            font-family: var(--theme-body-font, Inter, system-ui, sans-serif);
        }

        [data-capell-theme='saas'] section {
            padding: clamp(4rem, 7vw, 7rem) 1.5rem;
        }

        [data-capell-theme='saas'] section > div {
            max-width: 74rem;
            margin-inline: auto;
        }

        [data-capell-theme='saas'] h1,
        [data-capell-theme='saas'] h2 {
            max-width: 14ch;
            color: #0f172a;
            font-family: var(--theme-heading-font, inherit);
            font-size: clamp(3rem, 7vw, 5.75rem);
            font-weight: 800;
            line-height: 0.98;
            letter-spacing: 0;
        }

        [data-capell-theme='saas'] p {
            max-width: 42rem;
            color: #475569;
            font-size: 1.125rem;
            line-height: 1.8;
        }

        [data-capell-theme='saas'] a {
            color: var(--theme-primary, #6366f1);
        }

        [data-capell-theme='saas'] img,
        [data-capell-theme='saas'] section div:empty {
            border-radius: 1rem;
        }
    </style>
@endonce

<div
    data-capell-theme="{{ $themeKey }}"
    style="{{ collect($brand->tokens())->map(fn ($value, $token) => $token . ':' . $value)->implode(';') }}"
    class="min-h-screen bg-white text-slate-950 antialiased"
>
    {!! $content !!}
</div>
