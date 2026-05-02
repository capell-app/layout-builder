<div class="space-y-4">
    @if (! $hasReport)
        <div class="text-sm text-gray-600 dark:text-gray-300">
            {{ __('capell-seo-tools::generic.seo_panel_empty_state') }}
        </div>
    @else
        <section class="space-y-3">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div
                        class="text-sm font-medium text-gray-950 dark:text-white"
                    >
                        {{ __('capell-seo-tools::generic.seo_panel_overview') }}
                    </div>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        {{ __('capell-seo-tools::generic.seo_panel_passed_checks', ['count' => count($passedCheckValues)]) }}
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    {{ $schemaComponent->getAction('ai_content_brief') }}

                    <div
                        class="rounded-md bg-gray-50 px-3 py-2 text-right dark:bg-gray-800"
                    >
                        <div
                            class="text-xs font-medium text-gray-500 dark:text-gray-400"
                        >
                            {{ __('capell-seo-tools::generic.seo_panel_score') }}
                        </div>
                        <div
                            class="text-2xl font-semibold text-gray-950 dark:text-white"
                        >
                            {{ $report->score }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div
                    class="rounded-md border border-gray-200 p-3 dark:border-gray-700"
                >
                    <div
                        class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400"
                    >
                        {{ __('capell-seo-tools::generic.seo_panel_search_preview') }}
                    </div>
                    <div
                        class="text-primary-600 dark:text-primary-400 mt-2 text-base font-medium"
                    >
                        {{ $report->searchPreview->title }}
                    </div>
                    <div
                        class="mt-1 text-xs text-green-700 dark:text-green-400"
                    >
                        {{ $report->searchPreview->url }}
                    </div>
                    <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        {{ $report->searchPreview->description }}
                    </div>
                </div>

                <div
                    class="rounded-md border border-gray-200 p-3 dark:border-gray-700"
                >
                    <div
                        class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400"
                    >
                        {{ __('capell-seo-tools::generic.seo_panel_social_preview') }}
                    </div>
                    <div
                        class="mt-2 text-base font-medium text-gray-950 dark:text-white"
                    >
                        {{ $report->socialPreview->title }}
                    </div>
                    <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        {{ $report->socialPreview->description }}
                    </div>
                    @if ($report->socialPreview->imageUrl !== null)
                        <div
                            class="mt-2 text-xs text-gray-500 dark:text-gray-400"
                        >
                            {{ $report->socialPreview->imageUrl }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                @foreach ([
                              __('capell-seo-tools::generic.seo_severity_critical') => $overviewIssues['critical'],
                              __('capell-seo-tools::generic.seo_severity_warning') => $overviewIssues['warning'],
                              __('capell-seo-tools::generic.seo_severity_notice') => $overviewIssues['notice'],
                          ] as $severityLabel => $issues)
                    <div
                        class="rounded-md border border-gray-200 p-3 dark:border-gray-700"
                    >
                        <div
                            class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400"
                        >
                            {{ $severityLabel }} ({{ count($issues) }})
                        </div>

                        @if ($issues === [])
                            <div
                                class="mt-2 text-sm text-gray-600 dark:text-gray-300"
                            >
                                {{ __('capell-seo-tools::generic.seo_panel_section_clear') }}
                            </div>
                        @else
                            <ul
                                class="mt-2 space-y-2 text-sm text-gray-700 dark:text-gray-300"
                            >
                                @foreach ($issues as $issue)
                                    <li>{{ $issue->message }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        <section class="border-t border-gray-200 pt-4 dark:border-gray-700">
            <div class="text-sm font-medium text-gray-950 dark:text-white">
                {{ __('capell-seo-tools::generic.seo_panel_links') }}
            </div>
            @if ($linkIssues === [] && $report->internalLinkSuggestions === [])
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    {{ __('capell-seo-tools::generic.seo_panel_section_clear') }}
                </div>
            @else
                <ul
                    class="mt-2 space-y-2 text-sm text-gray-700 dark:text-gray-300"
                >
                    @foreach ($linkIssues as $issue)
                        <li>{{ $issue->message }}</li>
                    @endforeach

                    @foreach ($report->internalLinkSuggestions as $suggestion)
                        <li>
                            <span class="font-medium">
                                {{ $suggestion->title }}
                            </span>
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ $suggestion->url }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="border-t border-gray-200 pt-4 dark:border-gray-700">
            <div class="text-sm font-medium text-gray-950 dark:text-white">
                {{ __('capell-seo-tools::generic.seo_panel_schema') }}
            </div>
            @if ($schemaIssues === [] && $report->schemaReports === [])
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    {{ __('capell-seo-tools::generic.seo_panel_section_clear') }}
                </div>
            @else
                <ul
                    class="mt-2 space-y-2 text-sm text-gray-700 dark:text-gray-300"
                >
                    @foreach ($schemaIssues as $issue)
                        <li>{{ $issue->message }}</li>
                    @endforeach

                    @foreach ($report->schemaReports as $schemaReport)
                        <li>
                            <span class="font-medium">
                                {{ $schemaReport->templateType->getLabel() }}
                            </span>
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ $schemaReport->severity->getLabel() }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="border-t border-gray-200 pt-4 dark:border-gray-700">
            <div class="text-sm font-medium text-gray-950 dark:text-white">
                {{ __('capell-seo-tools::generic.seo_panel_redirects') }}
                ({{ count($redirectOpportunities) }})
            </div>
            @if ($redirectOpportunities === [])
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    {{ __('capell-seo-tools::generic.seo_panel_no_redirects') }}
                </div>
            @else
                <ul
                    class="mt-2 space-y-2 text-sm text-gray-700 dark:text-gray-300"
                >
                    @foreach ($redirectOpportunities as $opportunity)
                        <li>
                            <span class="font-medium">
                                {{ $opportunity->sourceUrl }}
                            </span>
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ __('capell-seo-tools::generic.seo_panel_redirect_hits', ['count' => $opportunity->hits]) }}
                            </span>
                            @if ($opportunity->suggestedTargetUrl !== null)
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ $opportunity->suggestedTargetUrl }}
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="border-t border-gray-200 pt-4 dark:border-gray-700">
            <div class="text-sm font-medium text-gray-950 dark:text-white">
                {{ __('capell-seo-tools::generic.seo_panel_search_console') }}
            </div>
            @if ($searchConsoleIssues === [] && $report->searchConsoleInsights === [])
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    {{ __('capell-seo-tools::generic.seo_panel_section_clear') }}
                </div>
            @else
                <ul
                    class="mt-2 space-y-2 text-sm text-gray-700 dark:text-gray-300"
                >
                    @foreach ($searchConsoleIssues as $issue)
                        <li>{{ $issue->message }}</li>
                    @endforeach

                    @foreach ($report->searchConsoleInsights as $insight)
                        <li>
                            <span class="font-medium">
                                {{ $insight->metric->getLabel() }}
                            </span>
                            <span>{{ $insight->message }}</span>
                            @if ($insight->value !== null)
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ $insight->value }}
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="border-t border-gray-200 pt-4 dark:border-gray-700">
            <div class="text-sm font-medium text-gray-950 dark:text-white">
                {{ __('capell-seo-tools::generic.seo_panel_robots_canonical') }}
            </div>
            <div
                class="mt-2 grid gap-3 text-sm text-gray-700 md:grid-cols-2 dark:text-gray-300"
            >
                <div>
                    <div
                        class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400"
                    >
                        {{ __('capell-seo-tools::generic.seo_panel_canonical') }}
                    </div>
                    <div class="mt-1">
                        {{ $report->canonicalUrl ?? $report->searchPreview->url }}
                    </div>
                </div>
                <div>
                    <div
                        class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400"
                    >
                        {{ __('capell-seo-tools::generic.seo_panel_robots') }}
                    </div>
                    <div class="mt-1">
                        {{ $report->robotsDirectives === [] ? __('capell-seo-tools::generic.seo_panel_default_robots') : implode(', ', $report->robotsDirectives) }}
                    </div>
                </div>
            </div>

            @if ($robotsIssues === [])
                <div class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                    {{ __('capell-seo-tools::generic.seo_panel_section_clear') }}
                </div>
            @else
                <ul
                    class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300"
                >
                    @foreach ($robotsIssues as $issue)
                        <li>{{ $issue->message }}</li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="border-t border-gray-200 pt-4 dark:border-gray-700">
            <div class="text-sm font-medium text-gray-950 dark:text-white">
                {{ __('capell-seo-tools::generic.seo_panel_passed_checks_heading') }}
                ({{ count($passedCheckValues) }})
            </div>
            @if ($passedCheckValues === [])
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    {{ __('capell-seo-tools::generic.seo_panel_no_suggestions') }}
                </div>
            @else
                <ul
                    class="mt-2 flex flex-wrap gap-2 text-sm text-gray-700 dark:text-gray-300"
                >
                    @foreach ($passedCheckValues as $passedCheckValue)
                        <li
                            class="rounded-md bg-gray-50 px-2 py-1 dark:bg-gray-800"
                        >
                            {{ __('capell-seo-tools::generic.seo_check_' . $passedCheckValue) }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif
</div>
