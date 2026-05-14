<?php

declare(strict_types=1);

it('declares the admin resources and extension points owned by layout builder', function (): void {
    $manifest = json_decode(
        file_get_contents(dirname(__DIR__, 2) . '/capell.json') ?: '[]',
        true,
    );

    $contributionTypes = collect($manifest['contributes'] ?? [])->pluck('type')->all();
    $deferredTypes = $manifest['contributionTraceability']['deferredContributions'] ?? [];

    expect($contributionTypes)->toContain('admin-resource', 'configurator', 'schema-extender', 'asset')
        ->and($deferredTypes)->not->toContain('permission', 'configurator', 'schema-extender', 'asset');
});
