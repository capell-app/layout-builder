@php
    use Capell\Analytics\Providers\AnalyticsServiceProvider;

    $analyticsConfig = [
        'eventsUrl' => route('capell-analytics.events'),
        'consentUrl' => route('capell-analytics.consent'),
        'trackPageViews' => config('capell-analytics.track_page_views', true) === true
            && config('capell-frontend.record_page_views', true) === true,
        'trackClicks' => config('capell-analytics.track_clicks', true) === true,
        'automaticClickTracking' => config('capell-analytics.automatic_click_tracking', true) === true,
        'ignoredSelectors' => config('capell-analytics.ignored_selectors', []),
        'policyVersion' => config('capell-analytics.policy_version', '1.0'),
    ];

    $analyticsScriptPath = dirname((new ReflectionClass(AnalyticsServiceProvider::class))->getFileName(), 3) . '/resources/js/capell-analytics.js';
@endphp

<script type="application/json" data-capell-analytics-tracker>
    {!! json_encode($analyticsConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) !!}
</script>
<script>
    {!! file_get_contents($analyticsScriptPath) !!}
</script>
