<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\ResolveLayoutAreaContainersAction;
use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;

it('resolves main containers while preserving legacy containers without area metadata', function (): void {
    $containers = [
        'legacy' => ['widgets' => [], 'meta' => []],
        'main' => ['widgets' => [], 'meta' => ['area' => LayoutAreaRegistry::MAIN]],
        'header' => ['widgets' => [], 'meta' => ['area' => 'header']],
    ];

    $resolved = ResolveLayoutAreaContainersAction::run($containers);

    expect($resolved)->toHaveKeys(['legacy', 'main'])
        ->and($resolved)->not->toHaveKey('header');
});

it('resolves named chrome area containers', function (): void {
    $containers = [
        'main' => ['widgets' => [], 'meta' => ['area' => LayoutAreaRegistry::MAIN]],
        'header' => ['widgets' => [], 'meta' => ['area' => 'header']],
    ];

    $resolved = ResolveLayoutAreaContainersAction::run($containers, 'header');

    expect($resolved)->toHaveKey('header')
        ->and($resolved)->not->toHaveKey('main');
});

it('normalizes requested area keys before matching containers', function (): void {
    $containers = [
        'header' => ['widgets' => [], 'meta' => ['area' => 'header']],
    ];

    expect(ResolveLayoutAreaContainersAction::run($containers, 'Header'))->toHaveKey('header');
});

it('returns an empty list for missing container data', function (): void {
    expect(ResolveLayoutAreaContainersAction::run(null))->toBe([]);
});
