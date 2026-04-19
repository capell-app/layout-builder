@props(['analytics' => null, 'utm' => null])

@if ($analytics && $analytics->enabled())
    {!! $analytics->initScript() !!}
    @if ($utm)
        <script>{!! $utm->toJavaScript() !!}</script>
    @endif
@endif
