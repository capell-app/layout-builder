<div
    data-theme-studio-theme="{{ $themeKey }}"
    style="{{ collect($brand->tokens())->map(fn ($value, $token) => $token . ':' . $value)->implode(';') }}"
    class="bg-white text-slate-950"
>
    {!! $content !!}
</div>
