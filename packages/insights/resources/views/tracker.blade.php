@php
    use Capell\Insights\Providers\InsightsServiceProvider;

    $insightsConfig = [
        'eventsUrl' => route('capell-insights.events'),
        'consentUrl' => route('capell-insights.consent'),
        'trackPageViews' => config('capell-insights.track_page_views', true) === true,
        'trackClicks' => config('capell-insights.track_clicks', true) === true,
        'automaticClickTracking' => config('capell-insights.automatic_click_tracking', true) === true,
        'ignoredSelectors' => config('capell-insights.ignored_selectors', []),
        'policyVersion' => config('capell-insights.policy_version', '1.0'),
    ];

    $insightsScriptPath = dirname((new ReflectionClass(InsightsServiceProvider::class))->getFileName(), 3) . '/resources/js/capell-insights.js';
@endphp

<script type="application/json" data-capell-insights-tracker>
    {!! json_encode($insightsConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) !!}
</script>
<script>
    {!! file_get_contents($insightsScriptPath) !!}
</script>
