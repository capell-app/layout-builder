<?php

declare(strict_types=1);

it('separates inspected release evidence from optional replacement targets', function (): void {
    $entries = collect(layout_builder_screenshot_entries());

    $requiredIds = [
        'layout-builder-editor-main-sidebar',
        'layout-builder-edit-container-theme-settings',
        'layout-builder-edit-container-action-mobile',
        'layout-builder-edit-container-theme-settings-mobile',
        'widgets-admin-index',
        'create-edit-widget-form',
        'sections-admin-index',
    ];

    $optionalReplacementIds = [
        'layout-builder-editor-content-first',
        'layout-builder-responsive-preview',
        'layout-builder-tree-selection',
        'layout-builder-tree-keyboard-search',
        'layout-builder-preset-action',
        'layout-builder-undo-redo-actions',
        'layout-builder-bulk-change-criteria',
        'layout-builder-bulk-change-review',
        'layout-example-main-sidebar-admin',
        'layout-example-main-sidebar-public',
        'layout-example-full-width-public',
    ];

    $deferredMarketplaceIds = [
        'layout-builder-add-widget-action',
        'layout-builder-add-container-action',
        'layout-builder-edit-widget-action',
        'layout-builder-edit-container-action',
    ];

    expect($entries->pluck('id')->all())
        ->toContain(...$requiredIds)
        ->toContain(...$optionalReplacementIds)
        ->toContain(...$deferredMarketplaceIds);

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

    foreach ($deferredMarketplaceIds as $deferredMarketplaceId) {
        $entry = $entries->firstWhere('id', $deferredMarketplaceId);

        expect($entry)
            ->not->toBeNull()
            ->and($entry['required'] ?? true)->toBeFalse()
            ->and($entry['notes'] ?? '')->toBe('Deferred: the current fixture evidence has been retired. Replace it with an authentic installed-App route capture before Marketplace promotion.');
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

it('captures widget integrity states through authenticated production table presenters', function (): void {
    $entries = collect(layout_builder_screenshot_entries())
        ->whereIn('id', [
            'layout-builder-unused-disabled-widget',
            'layout-builder-broken-unscoped-widget-assets',
        ])
        ->keyBy('id');

    expect($entries)->toHaveCount(2);

    expect($entries['layout-builder-unused-disabled-widget'])
        ->toMatchArray([
            'surface' => 'admin',
            'scenario' => 'admin-index',
            'url' => '/screenshot-fixtures/layout-builder-integrity/widget-usage',
            'waitFor' => '[data-layout-builder-integrity-table="widgets"] table',
            'target' => 'WidgetsTable',
            'required' => false,
            'user' => 'admin',
            'interactions' => [
                ['type' => 'waitFor', 'selector' => '[data-layout-builder-integrity-table="widgets"] tr:has-text("Screenshot unused and disabled widget") .fi-badge'],
                ['type' => 'waitFor', 'selector' => '[data-layout-builder-integrity-table="widgets"] tr:has-text("Screenshot unused and disabled widget") div.table-cell-action-icon svg'],
            ],
        ]);

    expect($entries['layout-builder-broken-unscoped-widget-assets'])
        ->toMatchArray([
            'surface' => 'admin',
            'scenario' => 'admin-index',
            'url' => '/screenshot-fixtures/layout-builder-integrity/widget-assets',
            'waitFor' => '[data-layout-builder-integrity-table="widget-assets"] table',
            'target' => 'WidgetAssetsTable',
            'required' => false,
            'user' => 'admin',
            'interactions' => [
                ['type' => 'waitFor', 'selector' => '[data-layout-builder-integrity-table="widget-assets"] tr:has-text("Broken asset") .fi-badge'],
                ['type' => 'waitFor', 'selector' => '[data-layout-builder-integrity-table="widget-assets"] tr:has-text("Not placed") .fi-badge'],
            ],
        ]);
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
            ['type' => 'waitFor', 'selector' => '[data-layout-container-section="appearance"]'],
            ['type' => 'click', 'selector' => '[data-layout-container-control="responsive-padding"][role="switch"]'],
            ['type' => 'waitFor', 'selector' => '[data-layout-container-field="padding-tablet"]'],
        ],
        'layout-builder-edit-container-theme-settings' => [
            ['type' => 'click', 'selector' => '[data-layout-builder-tree-item="main"]'],
            ['type' => 'waitFor', 'selector' => '[data-layout-builder-action="edit-container"]:visible'],
            ['type' => 'click', 'selector' => '[data-layout-builder-action="edit-container"]:visible'],
            ['type' => 'waitFor', 'selector' => '.fi-modal-window:visible'],
            ['type' => 'click', 'selector' => '[data-layout-container-section="theme"] .fi-section-header'],
            ['type' => 'scrollIntoView', 'selector' => '[data-layout-container-section="theme"]'],
            ['type' => 'waitFor', 'selector' => '[data-layout-container-action="reset-theme-settings"]:visible'],
        ],
        'layout-builder-responsive-preview' => [
            ['type' => 'click', 'selector' => '[data-layout-builder-action="preview-tablet"]'],
            ['type' => 'waitFor', 'selector' => '[data-layout-builder-breakpoint="tablet"]'],
        ],
        'layout-builder-tree-selection' => [
            ['type' => 'click', 'selector' => '[data-layout-builder-tree-item="main"]'],
            ['type' => 'waitFor', 'selector' => '[data-layout-builder-selected="true"]'],
        ],
        'layout-builder-tree-keyboard-search' => [
            ['type' => 'fill', 'selector' => 'input[type="search"]', 'value' => 'Screenshot hero'],
            ['type' => 'waitForTimeout', 'timeout' => 400],
            ['type' => 'press', 'selector' => '[data-layout-builder-tree-item="main"]', 'value' => 'ArrowRight'],
            ['type' => 'waitFor', 'selector' => "[data-layout-builder-tree-widget][data-layout-builder-tree-search*='Screenshot hero']:focus"],
            ['type' => 'waitFor', 'selector' => "[data-layout-builder-tree-item='sidebar']:not(:visible)"],
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

it('opens the mobile structure drawer before selecting the container inspector action', function (): void {
    $entries = collect(layout_builder_screenshot_entries())->keyBy('id');

    $expectedInteractions = [
        ['type' => 'click', 'selector' => '.layout-builder-panel-toggle[title="Layout structure"]'],
        ['type' => 'waitFor', 'selector' => '[data-layout-builder-tree-item="main"] .layout-builder-tree-row-container:visible'],
        ['type' => 'click', 'selector' => '[data-layout-builder-tree-item="main"] .layout-builder-tree-row-container:visible'],
        ['type' => 'waitFor', 'selector' => '[data-layout-builder-action="edit-container"]:visible'],
        ['type' => 'click', 'selector' => '[data-layout-builder-action="edit-container"]:visible'],
        ['type' => 'waitFor', 'selector' => '.fi-modal-window:visible'],
    ];

    foreach (['layout-builder-edit-container-action-mobile', 'layout-builder-edit-container-theme-settings-mobile'] as $entryId) {
        $entry = $entries[$entryId] ?? null;

        expect($entry)
            ->not->toBeNull()
            ->and($entry['viewport'] ?? null)->toBe('mobile');

        $interactions = $entry['interactions'] ?? null;

        if (! is_array($interactions)) {
            throw new LogicException("Expected interaction evidence for [{$entryId}].");
        }

        expect($interactions)
            ->and(array_slice($interactions, 0, count($expectedInteractions)))->toBe($expectedInteractions)
            ->and(collect($interactions)->contains(static fn (mixed $interaction): bool => is_array($interaction) && ($interaction['type'] ?? null) === 'press'))->toBeFalse();
    }
});

it('keeps deferred page-building guide evidence out of required promotion', function (): void {
    $configuredCoreRepository = getenv('CAPELL_CORE_REPO_PATH');
    $documentationRepository = is_string($configuredCoreRepository) && $configuredCoreRepository !== ''
        ? rtrim($configuredCoreRepository, '/')
        : dirname(__DIR__, 5) . '/capell-4';
    $packageRepository = dirname(__DIR__, 4);
    $guide = file_get_contents($documentationRepository . '/docs/getting-started/building-pages.md');
    $entries = collect(layout_builder_screenshot_entries())->keyBy('id');

    $deferredEntries = [
        'layout-builder-add-container-action',
        'layout-builder-add-widget-action',
    ];

    expect($guide)
        ->toBeString()
        ->toContain('https://docs.capell.app/packages/layout-builder')
        ->not->toContain('page-building-layout-builder-editor.png')
        ->not->toContain('page-building-layout-builder-add-widget.png');

    foreach ($deferredEntries as $id) {
        $entry = $entries->get($id);

        throw_unless(is_array($entry), RuntimeException::class, sprintf('Expected the %s screenshot entry to be an array.', $id));

        $screenshotPath = $entry['screenshotPath'] ?? null;

        throw_unless(is_string($screenshotPath), RuntimeException::class, sprintf('Expected the %s screenshot path to be a string.', $id));

        expect($entry)
            ->and($entry['required'] ?? true)->toBeFalse()
            ->and(is_file($packageRepository . '/' . $screenshotPath))->toBeFalse()
            ->and($entry['notes'] ?? '')->toBe('Deferred: the current fixture evidence has been retired. Replace it with an authentic installed-App route capture before Marketplace promotion.')
            ->and($entry['useCase'] ?? '')->not->toBe('');
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
