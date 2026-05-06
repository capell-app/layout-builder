<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

arch('ai-orchestrator shared package surface does not import layout-builder')
    ->expect('Capell\AIOrchestrator')
    ->not->toUse('Capell\LayoutBuilder');

it('ai-orchestrator source contains no direct layout-builder references', function (): void {
    $packagePath = dirname(__DIR__, 2);
    $violations = [];

    $files = (new Finder)
        ->files()
        ->in($packagePath . '/src')
        ->name('*.php')
        ->contains('Capell\\LayoutBuilder');

    foreach ($files as $file) {
        $violations[] = str_replace($packagePath . '/', '', $file->getPathname());
    }

    expect($violations)->toBeEmpty();
});

arch()
    ->expect('Capell\AIOrchestrator')
    ->classes()
    ->toUseStrictEquality();
