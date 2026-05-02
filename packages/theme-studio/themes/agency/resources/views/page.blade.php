<div
    data-theme-studio-theme="{{ $themeKey }}"
    style="{{ collect($brand->tokens())->map(fn ($value, $token) => $token . ':' . $value)->implode(';') }}"
    class="bg-stone-50 text-zinc-950"
>
    {!! $content !!}
</div>
