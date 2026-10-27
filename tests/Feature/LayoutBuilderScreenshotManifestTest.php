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
        ->not->toContain('layout-example-main-sidebar-admin')
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
            ->and($entry['notes'] ?? '')->not->toBe('');
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

    expect($frontendEntries)->toHaveCount(4);

    $frontendEntries->each(static function (array $entry): void {
        expect($entry['user'] ?? null)->toBeFalse()
            ->and($entry['targetType'])->toBe('frontend-url')
            ->and($entry['url'] ?? '')->not->toStartWith('/admin/');
    });
});

it('keeps unstable action-state screenshots on deterministic anonymous fixture routes', function (): void {
    $fixtureEntries = collect(layout_builder_screenshot_entries())
        ->whereIn('id', [
            'layout-builder-preset-action',
            'layout-builder-undo-redo-actions',
        ])
        ->keyBy('id');

    expect($fixtureEntries)->toHaveCount(2);

    $expectedUrls = [
        'layout-builder-preset-action' => '/screenshot-fixtures/layout-builder/preset-action',
        'layout-builder-undo-redo-actions' => '/screenshot-fixtures/layout-builder/undo-redo-actions',
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

it('uses native persisted public layouts and a preview-only bulk change wizard', function (): void {
    $entries = collect(layout_builder_screenshot_entries())->keyBy('id');
    foreach ([
        'layout-example-main-sidebar-public' => '/studio-workshop-programme',
        'layout-example-full-width-public' => '/studio-workshop-programme-full-width',
    ] as $id => $url) {
        expect($entries[$id])->toMatchArray([
            'url' => $url,
            'surface' => 'frontend',
            'user' => false,
            'waitFor' => '.widget-default:has-text("Visitor information")',
        ]);
    }

    foreach (['layout-builder-bulk-change-criteria', 'layout-builder-bulk-change-review'] as $id) {
        expect($entries[$id])->toMatchArray([
            'url' => '/admin/layout-builder/layouts',
            'surface' => 'admin',
            'user' => 'admin',
            'waitFor' => '.fi-modal-window:visible',
        ]);
        $interactions = $entries[$id]['interactions'] ?? [];
        throw_unless(is_array($interactions), RuntimeException::class, 'Expected native bulk wizard interactions.');
        expect($interactions)->not->toBeEmpty();
        foreach ($interactions as $interaction) {
            throw_unless(is_array($interaction), RuntimeException::class, 'Expected a native bulk wizard interaction.');
            if (($interaction['type'] ?? null) === 'click') {
                expect($interaction['selector'] ?? '')->not->toContain('Approve and apply');
            }
        }
    }
});

it('captures widget integrity states through authenticated native widget screens', function (): void {
    $entries = collect(layout_builder_screenshot_entries())->keyBy('id');
    foreach ([
        'layout-builder-unused-disabled-widget' => ['admin-index', 'WidgetResource/ListWidgets'],
        'layout-builder-broken-unscoped-widget-assets' => ['admin-form', 'WidgetResource/EditWidget/WidgetAssetsRelationManager'],
    ] as $id => [$scenario, $target]) {
        expect($entries[$id])->toMatchArray([
            'surface' => 'admin',
            'scenario' => $scenario,
            'url' => '/admin/layout-builder/widgets',
            'waitFor' => 'table',
            'target' => $target,
            'required' => false,
            'user' => 'admin',
        ]);
        expect($entries[$id]['interactions'] ?? [])->not->toBeEmpty();
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
            ['type' => 'click', 'selector' => '[data-layout-builder-tree-widget]:visible'],
            ['type' => 'waitFor', 'selector' => '[data-layout-builder-tree-widget][data-layout-builder-selected="true"]:visible'],
        ],
        'layout-builder-tree-keyboard-search' => [
            ['type' => 'fill', 'selector' => '.layout-builder-tree-search input:visible', 'value' => 'Workshop overview'],
            ['type' => 'waitForTimeout', 'timeout' => 400],
            ['type' => 'press', 'selector' => '[data-layout-builder-tree-item="main"]:visible', 'value' => 'ArrowRight'],
            ['type' => 'waitFor', 'selector' => "[data-layout-builder-tree-widget][data-layout-builder-tree-search*='Workshop overview']:focus"],
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
    // The Core guide that must not embed this evidence is asserted in tests/Feature/LayoutBuilderDeferredGuideEvidenceTest.php:
    // this suite is a package attestation check, so it reads nothing from the Core checkout outside the keyed Core code.
    $packageRepository = dirname(__DIR__, 4);
    $entries = collect(layout_builder_screenshot_entries())->keyBy('id');

    $deferredEntries = [
        'layout-builder-add-container-action',
        'layout-builder-add-widget-action',
    ];

    foreach ($deferredEntries as $id) {
        $entry = $entries->get($id);

        throw_unless(is_array($entry), RuntimeException::class, sprintf('Expected the %s screenshot entry to be an array.', $id));

        $screenshotPath = $entry['screenshotPath'] ?? null;

        throw_unless(is_string($screenshotPath), RuntimeException::class, sprintf('Expected the %s screenshot path to be a string.', $id));

        expect($entry)
            ->and($entry['required'] ?? true)->toBeFalse()
            ->and(is_file($packageRepository . '/' . $screenshotPath))->toBeTrue()
            ->and($entry['notes'] ?? '')->toBe('Deferred: the current fixture evidence has been retired. Replace it with an authentic installed-App route capture before Marketplace promotion.')
            ->and($entry['useCase'] ?? '')->not->toBe('');

        $receiptPath = $packageRepository . '/docs/screenshot-receipts/native-layout-actions-20261020/layout-builder--' . $id . '.json';
        $report = json_decode((string) file_get_contents($receiptPath), true, flags: JSON_THROW_ON_ERROR);
        throw_unless(is_array($report), RuntimeException::class, 'Expected an accepted screenshot report.');
        $provenance = $report['provenance'] ?? null;
        throw_unless(is_array($provenance), RuntimeException::class, 'Expected screenshot provenance.');
        $receipts = $provenance['receipts'] ?? null;
        throw_unless(is_array($receipts), RuntimeException::class, 'Expected screenshot receipts.');
        $receipt = collect($receipts)->firstWhere('id', $id);
        throw_unless(is_array($receipt), RuntimeException::class, 'Expected the accepted screenshot receipt.');
        $output = $receipt['output'] ?? null;
        throw_unless(is_array($output), RuntimeException::class, 'Expected the accepted screenshot output.');
        expect($output['path'] ?? null)->toBe($screenshotPath)
            ->and($output['sha256'] ?? null)->toBe(hash_file('sha256', $packageRepository . '/' . $screenshotPath))
            ->and($provenance['generator'] ?? null)->toBe('shared-capell-screenshot-runner');
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
