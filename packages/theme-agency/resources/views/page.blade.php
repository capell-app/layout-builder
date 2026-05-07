@once
    <style>
        [data-capell-theme='agency'] {
            background: #09090b;
            color: #f8fafc;
            font-family: var(--theme-body-font, Inter, system-ui, sans-serif);
        }

        [data-capell-theme='agency'] section {
            padding: clamp(4rem, 8vw, 8rem) 1.5rem;
        }

        [data-capell-theme='agency'] section > div {
            max-width: 76rem;
            margin-inline: auto;
        }

        [data-capell-theme='agency'] h1,
        [data-capell-theme='agency'] h2 {
            max-width: 12ch;
            color: #fff;
            font-family: var(--theme-heading-font, inherit);
            font-size: clamp(3rem, 8vw, 6.5rem);
            font-weight: 900;
            line-height: 0.92;
            letter-spacing: 0;
        }

        [data-capell-theme='agency'] p {
            max-width: 42rem;
            color: rgb(255 255 255 / 72%);
            font-size: 1.125rem;
            line-height: 1.8;
        }

        [data-capell-theme='agency'] a {
            color: inherit;
        }

        [data-capell-theme='agency'] img,
        [data-capell-theme='agency'] section div:empty {
            border-radius: 1.5rem;
        }
    </style>
@endonce

<div
    data-capell-theme="{{ $themeKey }}"
    style="{{ collect($brand->tokens())->map(fn ($value, $token) => $token . ':' . $value)->implode(';') }}"
    class="min-h-screen bg-zinc-950 text-zinc-950 antialiased"
>
    {!! $content !!}
</div>
