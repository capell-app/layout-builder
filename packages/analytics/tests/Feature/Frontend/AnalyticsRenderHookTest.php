<?php

declare(strict_types=1);

use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;

it('injects the frontend analytics tracker at the end of the body', function (): void {
    config()->set('capell-analytics.ignored_selectors', [
        '[data-capell-analytics-ignore]',
        '[wire\\:click]',
    ]);

    /** @var RenderHookRegistry $registry */
    $registry = resolve(RenderHookRegistry::class);

    $output = $registry->renderAll(RenderHookLocation::BodyEnd);

    expect($output)
        ->toContain('data-capell-analytics-tracker')
        ->toContain(route('capell-analytics.events'))
        ->toContain(route('capell-analytics.consent'))
        ->toContain('"ignoredSelectors":["[data-capell-analytics-ignore]","[wire\\\\:click]"]');
});
