<?php

declare(strict_types=1);

it('contains the browser tracking primitives', function (): void {
    $source = file_get_contents(__DIR__ . '/../../../resources/js/capell-insights.js');

    expect($source)
        ->toContain('navigator.sendBeacon')
        ->toContain('keepalive: true')
        ->toContain('data-capell-insights-ignore')
        ->toContain('data-capell-insights-label')
        ->toContain('data-capell-insights-location');
});

it('uses response-reading fetch for consent submissions', function (): void {
    $source = file_get_contents(__DIR__ . '/../../../resources/js/capell-insights.js');
    $consentSource = scriptFunctionBody($source, 'consent');

    expect($consentSource)
        ->toContain('fetch(config.consentUrl')
        ->toContain('keepalive: true')
        ->toContain('storeVisitId(response.visit_id)')
        ->not->toContain('sendJson(')
        ->not->toContain('navigator.sendBeacon');
});

it('falls back to the server visit cookie when local storage is empty', function (): void {
    $source = file_get_contents(__DIR__ . '/../../../resources/js/capell-insights.js');

    expect($source)
        ->toContain("var visitCookieName = 'capell_insights_visit'")
        ->toContain('document.cookie')
        ->toContain('currentVisitCookie()')
        ->toContain('return storedVisitId || currentVisitCookie()');
});

it('does not persist raw dom ids in target selectors', function (): void {
    $source = file_get_contents(__DIR__ . '/../../../resources/js/capell-insights.js');
    $selectorSource = scriptFunctionBody($source, 'selectorFor');

    expect($selectorSource)
        ->not->toContain('element.id')
        ->not->toContain("'#' +")
        ->not->toContain('CSS.escape');
});

function scriptFunctionBody(string $source, string $functionName): string
{
    $pattern = sprintf('/%s: function \\(payload\\) \\{(?P<body>.*?)\\n        \\}/s', preg_quote($functionName, '/'));

    if (preg_match($pattern, $source, $matches) === 1) {
        return $matches['body'];
    }

    $pattern = sprintf('/function %s\\([^)]*\\) \\{(?P<body>.*?)\\n    \\}/s', preg_quote($functionName, '/'));

    preg_match($pattern, $source, $matches);

    return $matches['body'] ?? '';
}
