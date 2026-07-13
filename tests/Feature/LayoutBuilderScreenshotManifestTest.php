<?php

declare(strict_types=1);

it('separates inspected release evidence from optional replacement targets', function (): void {
    $entries = collect(layout_builder_screenshot_entries());

    $requiredIds = [
        'layout-builder-add-widget-action',
        'layout-builder-add-container-action',
        'layout-builder-edit-widget-action',
        'layout-builder-edit-container-action',
        'widgets-admin-index',
        'create-edit-widget-form',
        'sections-admin-index',
    ];

    $optionalReplacementIds = [
        'layout-builder-editor-main-sidebar',
        'layout-builder-editor-content-first',
        'layout-builder-responsive-preview',
        'layout-builder-tree-selection',
        'layout-builder-preset-action',
        'layout-builder-undo-redo-actions',
        'layout-builder-bulk-change-criteria',
        'layout-builder-bulk-change-review',
        'layout-example-main-sidebar-admin',
        'layout-example-main-sidebar-public',
        'layout-example-full-width-public',
    ];

    expect($entries->pluck('id')->all())
        ->toContain(...$requiredIds)
        ->toContain(...$optionalReplacementIds);

    foreach ($requiredIds as $requiredId) {
        $entry = $entries->firstWhere('id', $requiredId);

        expect($entry)
            ->not->toBeNull()
            ->and($entry['required'] ?? false)->toBeTrue()
            ->and($entry['screenshotPath'] ?? '')->toStartWith('packages/layout-builder/docs/screenshots/')
            ->and($entry['useCase'] ?? '')->not->toBe('')
            ->and($entry['notes'] ?? '')->not->toBe('');
    }

    foreach ($optionalReplacementIds as $optionalReplacementId) {
        $entry = $entries->firstWhere('id', $optionalReplacementId);

        expect($entry)
            ->not->toBeNull()
            ->and($entry['required'] ?? true)->toBeFalse()
            ->and($entry['notes'] ?? '')->toStartWith('Optional replacement target.');
    }
});

it('marks frontend layout builder screenshots as anonymous visitor captures', function (): void {
    $frontendEntries = collect(layout_builder_screenshot_entries())
        ->where('surface', 'frontend')
        ->whereIn('id', [
            'layout-example-main-sidebar-public',
            'layout-example-full-width-public',
            'layout-builder-preset-action',
            'layout-builder-undo-redo-actions',
            'layout-builder-bulk-change-criteria',
            'layout-builder-bulk-change-review',
        ]);

    expect($frontendEntries)->toHaveCount(6);

    $frontendEntries->each(static function (array $entry): void {
        expect($entry['user'] ?? null)->toBeFalse()
            ->and($entry['targetType'])->toBe('frontend-url')
            ->and($entry['waitFor'] ?? null)->toBe('body')
            ->and($entry['url'] ?? '')->toStartWith('/screenshot-fixtures/layout-builder/');
    });
});

it('keeps unstable action-state screenshots on deterministic anonymous fixture routes', function (): void {
    $fixtureEntries = collect(layout_builder_screenshot_entries())
        ->whereIn('id', [
            'layout-builder-preset-action',
            'layout-builder-undo-redo-actions',
            'layout-builder-bulk-change-criteria',
            'layout-builder-bulk-change-review',
        ])
        ->keyBy('id');

    expect($fixtureEntries)->toHaveCount(4);

    $expectedUrls = [
        'layout-builder-preset-action' => '/screenshot-fixtures/layout-builder/preset-action',
        'layout-builder-undo-redo-actions' => '/screenshot-fixtures/layout-builder/undo-redo-actions',
        'layout-builder-bulk-change-criteria' => '/screenshot-fixtures/layout-builder/bulk-change-criteria',
        'layout-builder-bulk-change-review' => '/screenshot-fixtures/layout-builder/bulk-change-review',
    ];

    foreach ($expectedUrls as $entryId => $expectedUrl) {
        $entry = $fixtureEntries[$entryId];

        expect($entry['surface'] ?? null)->toBe('frontend')
            ->and($entry['scenario'] ?? null)->toBe('frontend-page')
            ->and($entry['url'] ?? null)->toBe($expectedUrl)
            ->and($entry['user'] ?? null)->toBeFalse()
            ->and($entry['targetType'] ?? null)->toBe('frontend-url')
            ->and($entry['waitFor'] ?? null)->toBe('body')
            ->and($entry['required'] ?? true)->toBeFalse()
            ->and($entry['notes'] ?? '')->toContain('illustration-only')
            ->and($entry)->not->toHaveKey('interactions');
    }
});

it('uses stable selector interactions for admin screenshot captures that the workbench can drive', function (): void {
    $entries = collect(layout_builder_screenshot_entries())->keyBy('id');

    $expectedInteractions = [
        'layout-builder-add-widget-action' => [
            ['type' => 'click', 'selector' => '[data-layout-builder-tree-item="main"]'],
            ['type' => 'waitFor', 'selector' => '[data-layout-builder-selected="true"]'],
            ['type' => 'click', 'selector' => '[data-layout-builder-action="add-widget"]:visible'],
            ['type' => 'waitFor', 'selector' => '.fi-modal-window:visible'],
        ],
        'layout-builder-add-container-action' => [
            ['type' => 'click', 'selector' => '[data-layout-builder-action="add-container"]:visible'],
            ['type' => 'waitFor', 'selector' => '.fi-modal-window:visible'],
        ],
        'layout-builder-edit-widget-action' => [
            ['type' => 'click', 'selector' => '[data-layout-builder-tree-widget]'],
            ['type' => 'waitFor', 'selector' => '[data-layout-builder-action="edit-widget"]:visible'],
            ['type' => 'click', 'selector' => '[data-layout-builder-action="edit-widget"]:visible'],
            ['type' => 'waitFor', 'selector' => '.fi-modal-window:visible'],
        ],
        'layout-builder-edit-container-action' => [
            ['type' => 'click', 'selector' => '[data-layout-builder-tree-item="main"]'],
            ['type' => 'waitFor', 'selector' => '[data-layout-builder-action="edit-container"]:visible'],
            ['type' => 'click', 'selector' => '[data-layout-builder-action="edit-container"]:visible'],
            ['type' => 'waitFor', 'selector' => '.fi-modal-window:visible'],
        ],
        'layout-builder-responsive-preview' => [
            ['type' => 'click', 'selector' => '[data-layout-builder-action="preview-tablet"]'],
            ['type' => 'waitFor', 'selector' => '[data-layout-builder-breakpoint="tablet"]'],
        ],
        'layout-builder-tree-selection' => [
            ['type' => 'click', 'selector' => '[data-layout-builder-tree-item="main"]'],
            ['type' => 'waitFor', 'selector' => '[data-layout-builder-selected="true"]'],
        ],
    ];

    foreach ($expectedInteractions as $entryId => $interactions) {
        $entry = $entries[$entryId] ?? null;

        expect($entry)->not->toBeNull()
            ->and($entry['surface'] ?? null)->toBe('admin')
            ->and($entry['targetType'] ?? null)->toBe('admin-surface')
            ->and($entry['url'] ?? null)->toBe('/screenshot-fixtures/layout-builder-admin-editor')
            ->and($entry['waitFor'] ?? null)->toBe('.layout-builder-visual-toolbar')
            ->and($entry['interactions'] ?? null)->toBe($interactions);
    }
});

it('keeps the canonical page-building guide evidence package-owned and traceable', function (): void {
    $documentationRepository = dirname(__DIR__, 5) . '/capell-4';
    $packageRepository = dirname(__DIR__, 4);
    $guide = file_get_contents($documentationRepository . '/docs/getting-started/building-pages.md');
    $entries = collect(layout_builder_screenshot_entries())->keyBy('id');

    $expectedEntries = [
        'layout-builder-add-container-action' => [
            'screenshotPath' => 'packages/layout-builder/docs/screenshots/layout-builder-add-container-action.png',
            'interactions' => [
                ['type' => 'click', 'selector' => '[data-layout-builder-action="add-container"]:visible'],
                ['type' => 'waitFor', 'selector' => '.fi-modal-window:visible'],
            ],
        ],
        'layout-builder-add-widget-action' => [
            'screenshotPath' => 'packages/layout-builder/docs/screenshots/layout-builder-add-widget-action.png',
            'interactions' => [
                ['type' => 'click', 'selector' => '[data-layout-builder-tree-item="main"]'],
                ['type' => 'waitFor', 'selector' => '[data-layout-builder-selected="true"]'],
                ['type' => 'click', 'selector' => '[data-layout-builder-action="add-widget"]:visible'],
                ['type' => 'waitFor', 'selector' => '.fi-modal-window:visible'],
            ],
        ],
    ];

    expect($guide)
        ->toBeString()
        ->toContain('https://docs.capell.app/packages/layout-builder')
        ->not->toContain('page-building-layout-builder-editor.png')
        ->not->toContain('page-building-layout-builder-add-widget.png');

    foreach ($expectedEntries as $id => $expectedEntry) {
        $entry = $entries->get($id);

        throw_unless(is_array($entry), RuntimeException::class, sprintf('Expected the %s screenshot entry to be an array.', $id));

        $screenshotPath = $entry['screenshotPath'] ?? null;

        throw_unless(is_string($screenshotPath), RuntimeException::class, sprintf('Expected the %s screenshot path to be a string.', $id));

        expect($entry)
            ->and($entry['required'] ?? false)->toBeTrue()
            ->and($screenshotPath)->toBe($expectedEntry['screenshotPath'])
            ->and(is_file($packageRepository . '/' . $screenshotPath))->toBeTrue()
            ->and($entry['notes'] ?? '')->not->toBe('')
            ->and($entry['useCase'] ?? '')->not->toBe('')
            ->and($entry['interactions'] ?? null)->toBe($expectedEntry['interactions']);
    }
});

/**
 * @return list<array<string, mixed>>
 */
function layout_builder_screenshot_entries(): array
{
    $manifestPath = dirname(__DIR__, 2) . '/docs/screenshots.json';
    $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);

    throw_unless(is_array($manifest), RuntimeException::class, 'Expected the Layout Builder screenshot manifest to decode to an array.');

    $entries = $manifest['entries'] ?? null;

    throw_unless(is_array($entries), RuntimeException::class, 'Expected the Layout Builder screenshot manifest to contain an entries array.');

    $normalizedEntries = [];

    foreach ($entries as $entry) {
        throw_unless(is_array($entry), RuntimeException::class, 'Expected every Layout Builder screenshot entry to be an array.');

        $normalizedEntry = [];

        foreach ($entry as $key => $value) {
            throw_unless(is_string($key), RuntimeException::class, 'Expected every Layout Builder screenshot entry key to be a string.');

            $normalizedEntry[$key] = $value;
        }

        $normalizedEntries[] = $normalizedEntry;
    }

    return $normalizedEntries;
}
