@once
    <style>
        [data-theme-studio-theme='agency'] {
            background: #09090b;
            color: #f8fafc;
            font-family: var(--theme-body-font, Inter, system-ui, sans-serif);
        }

        [data-theme-studio-theme='agency'] section {
            padding: clamp(4rem, 8vw, 8rem) 1.5rem;
        }

        [data-theme-studio-theme='agency'] section > div {
            max-width: 76rem;
            margin-inline: auto;
        }

        [data-theme-studio-theme='agency'] h1,
        [data-theme-studio-theme='agency'] h2 {
            max-width: 12ch;
            color: #fff;
            font-family: var(--theme-heading-font, inherit);
            font-size: clamp(3rem, 8vw, 6.5rem);
            font-weight: 900;
            line-height: 0.92;
            letter-spacing: 0;
        }

        [data-theme-studio-theme='agency'] p {
            max-width: 42rem;
            color: rgb(255 255 255 / 72%);
            font-size: 1.125rem;
            line-height: 1.8;
        }

        [data-theme-studio-theme='agency'] a {
            color: inherit;
        }

        [data-theme-studio-theme='agency'] img,
        [data-theme-studio-theme='agency'] section div:empty {
            border-radius: 1.5rem;
        }
    </style>
@endonce

<div
    data-theme-studio-theme="{{ $themeKey }}"
    style="{{ collect($brand->tokens())->map(fn ($value, $token) => $token . ':' . $value)->implode(';') }}"
    class="min-h-screen bg-zinc-950 text-zinc-950 antialiased"
>
    {!! $content !!}
</div>
