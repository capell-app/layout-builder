@props(['containerKey', 'occurrence', 'widgetData'])
@if ($widgetData['meta']['query_during_render'] ?? false)
    @php(\Illuminate\Support\Facades\DB::select('select 1 as forbidden_widget_query'))
@endif
<p
    data-fallback="{{ $containerKey }}:{{ $occurrence }}"
>Third-party widget {{ $containerKey }}:{{ $occurrence }}</p>
