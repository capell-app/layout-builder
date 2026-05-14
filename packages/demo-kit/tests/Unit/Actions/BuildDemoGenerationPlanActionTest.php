<?php

declare(strict_types=1);

use Capell\DemoKit\Actions\BuildDemoGenerationPlanAction;

it('builds repeatable demo plans when a seed is supplied', function (): void {
    $first = BuildDemoGenerationPlanAction::run([
        'site_count' => 2,
        'pages' => 8,
        'languages' => ['all'],
        'seed' => 1234,
    ]);

    $second = BuildDemoGenerationPlanAction::run([
        'site_count' => 2,
        'pages' => 8,
        'languages' => ['all'],
        'seed' => 1234,
    ]);

    expect($second->toArray())->toBe($first->toArray());
});

it('builds generated plans within requested scale controls', function (): void {
    $plan = BuildDemoGenerationPlanAction::run([
        'site_count' => 4,
        'pages' => 6,
        'languages' => ['random:2'],
        'seed' => 456,
    ]);

    expect($plan->sites)->toHaveCount(4)
        ->and($plan->languageCodes)->toHaveCount(2);

    foreach ($plan->sites as $site) {
        expect($site->pageCount())->toBe(6)
            ->and($site->languageCodes)->not()->toBeEmpty();
    }
});

it('honours page counts larger than the base page name pool', function (): void {
    $plan = BuildDemoGenerationPlanAction::run([
        'site_count' => 1,
        'pages' => 50,
        'languages' => ['en'],
        'seed' => 789,
    ]);

    expect($plan->sites[0]->pageCount())->toBe(50);
});

it('keeps generated demo page trees out of publishable config', function (): void {
    expect(config('capell-demo-kit.pages'))->toBeNull()
        ->and(config('capell-demo-kit.counts.pages_per_site'))->toBe([12, 30]);
});
