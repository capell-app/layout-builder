<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Actions\ApplyLayoutPresetAction;
use Capell\LayoutBuilder\Actions\Mutations\PasteLayoutFragmentAction;
use Capell\LayoutBuilder\Actions\SaveLayoutPresetAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutFragmentData;
use Capell\LayoutBuilder\Models\LayoutPreset;

it('persists site-scoped layout presets as layout-only snapshots by default', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => [
                'blocks' => [
                    [
                        'block_key' => 'hero',
                        'occurrence' => 1,
                        'meta' => [
                            'block_variant' => 'split-media',
                            'block_settings' => [
                                'anchor_id' => 'Hero Section',
                                'signed_url' => 'https://example.test/admin/signed',
                            ],
                            'admin_schema' => ['secret' => true],
                        ],
                        'editor_only' => 'not-needed',
                    ],
                ],
            ],
        ],
    ]);

    $preset = SaveLayoutPresetAction::run($layout, $site, 'Feature intro');

    expect($preset)->toBeInstanceOf(LayoutPreset::class)
        ->and($preset->site_id)->toBe($site->getKey())
        ->and($preset->key)->toBe('feature-intro')
        ->and($preset->scope)->toBe('layout_only')
        ->and($preset->snapshot['containers']['main']['blocks'][0])->toHaveKeys(['block_key', 'occurrence', 'meta'])
        ->and($preset->snapshot['containers']['main']['blocks'][0])->not->toHaveKey('editor_only')
        ->and(json_encode($preset->snapshot, JSON_THROW_ON_ERROR))->not->toContain('signed_url')
        ->and(json_encode($preset->snapshot, JSON_THROW_ON_ERROR))->not->toContain('admin_schema');
});

it('can persist a current editor container snapshot without reading stale layout containers', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['blocks' => []],
        ],
    ]);

    $preset = SaveLayoutPresetAction::run(
        layout: $layout,
        site: $site,
        name: 'Edited hero',
        category: 'hero',
        containers: [
            'hero' => [
                'blocks' => [
                    [
                        'block_key' => 'hero',
                        'occurrence' => 1,
                        'meta' => ['block_settings' => ['anchor_id' => 'Edited']],
                    ],
                ],
            ],
        ],
    );

    expect($preset->category)->toBe('hero')
        ->and($preset->snapshot['containers'])->toHaveKey('hero')
        ->and($preset->snapshot['containers'])->not->toHaveKey('main')
        ->and($preset->snapshot['containers']['hero']['blocks'][0]['meta']['block_settings']['anchor_id'])->toBe('Edited');
});

it('can save a site-scoped preset from a global layout', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->create([
        'site_id' => null,
        'containers' => [
            'main' => [
                'blocks' => [
                    ['block_key' => 'hero'],
                ],
            ],
        ],
    ]);

    $preset = SaveLayoutPresetAction::run($layout, $site, 'Shared hero');

    expect($preset->site_id)->toBe($site->getKey())
        ->and($preset->snapshot['containers'])->toHaveKey('main');
});

it('normalizes shorthand layout blocks when saving presets', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => [
                'blocks' => [
                    'hero',
                    [
                        'block_key' => 'cards',
                        'occurrence' => 2,
                    ],
                ],
            ],
        ],
    ]);

    $preset = SaveLayoutPresetAction::run($layout, $site, 'Shorthand layout');

    expect($preset->snapshot['containers']['main']['blocks'])->toBe([
        [
            'block_key' => 'hero',
            'occurrence' => 1,
            'meta' => [],
        ],
        [
            'block_key' => 'cards',
            'occurrence' => 2,
            'meta' => [],
        ],
    ]);
});

it('requires explicit replacement when saving over an existing preset key', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create(['containers' => []]);

    SaveLayoutPresetAction::run($layout, $site, 'Feature intro');

    SaveLayoutPresetAction::run($layout, $site, 'Feature intro');
})->throws(LogicException::class, 'already exists');

it('replaces an existing preset only when explicitly requested', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create(['containers' => []]);

    $preset = SaveLayoutPresetAction::run($layout, $site, 'Feature intro');
    $replacement = SaveLayoutPresetAction::run($layout, $site, 'Feature intro', replaceExisting: true);

    expect($replacement->getKey())->toBe($preset->getKey())
        ->and(LayoutPreset::query()->where('site_id', $site->getKey())->where('key', 'feature-intro')->count())->toBe(1);
});

it('strips unsafe metadata from starter content presets', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => [
                'blocks' => [
                    [
                        'block_key' => 'hero',
                        'occurrence' => 1,
                        'meta' => [
                            'content' => [
                                'heading' => 'Hero',
                                'data-model-id' => 123,
                                'edit_url' => 'https://example.test/admin/edit',
                                'Editor-Url' => 'https://example.test/admin/editor',
                                'ModelId' => 456,
                                'signed_url' => 'https://example.test/admin/signed',
                                'signed-editor-url' => 'https://example.test/admin/signed-editor-url',
                            ],
                            'block_settings' => ['anchor_id' => 'Hero'],
                            'public_view' => 'admin.secret',
                        ],
                        'content' => [
                            'heading' => 'Hero',
                            'schema' => ['secret' => true],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $preset = SaveLayoutPresetAction::run($layout, $site, 'Starter hero', includeStarterContent: true);

    expect(json_encode($preset->snapshot, JSON_THROW_ON_ERROR))
        ->not->toContain('signed_url')
        ->not->toContain('data-model-id')
        ->not->toContain('edit_url')
        ->not->toContain('Editor-Url')
        ->not->toContain('ModelId')
        ->not->toContain('signed-editor-url')
        ->not->toContain('public_view')
        ->not->toContain('schema');
});

it('strips unsafe preset metadata values', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => [
                'blocks' => [
                    [
                        'block_key' => 'hero',
                        'meta' => [
                            'block_key' => 'package_pricing',
                            'block_variant' => 'schema-markup',
                            'block_settings' => [
                                'anchor_id' => 'Package pricing',
                                'cta_url' => 'https://example.test/admin/signed-editor-url?signature=secret',
                                'secondary_cta_url' => '/admin/pages/1?signature=secret',
                                'preview_url' => '/livewire/preview?signed_editor_url=secret',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $preset = SaveLayoutPresetAction::run($layout, $site, 'Unsafe values');
    $encodedSnapshot = json_encode($preset->snapshot, JSON_THROW_ON_ERROR);

    expect($encodedSnapshot)
        ->toContain('package_pricing')
        ->toContain('schema-markup')
        ->toContain('Package pricing')
        ->not->toContain('https://example.test/admin/signed-editor-url?signature=secret')
        ->not->toContain('/admin/pages/1?signature=secret')
        ->not->toContain('/livewire/preview?signed_editor_url=secret');
});

it('applies presets only within the same site and uniques duplicate anchors', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create(['containers' => []]);
    $preset = LayoutPreset::factory()->create([
        'site_id' => $site->getKey(),
        'snapshot' => [
            'containers' => [
                'main' => [
                    'blocks' => [
                        [
                            'block_key' => 'hero',
                            'meta' => ['block_settings' => ['anchor_id' => 'Feature Grid']],
                        ],
                        [
                            'block_key' => 'cards',
                            'meta' => ['block_settings' => ['anchor_id' => 'Feature Grid']],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $updatedLayout = ApplyLayoutPresetAction::run($preset, $layout, $site);

    expect($updatedLayout->containers['main']['blocks'][0]['meta']['block_settings']['anchor_id'])->toBe('feature-grid')
        ->and($updatedLayout->containers['main']['blocks'][1]['meta']['block_settings']['anchor_id'])->toBe('feature-grid-2')
        ->and($layout->fresh()->containers)->toBe([]);
});

it('can persist an applied preset after an explicit confirmation path', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create(['containers' => []]);
    $preset = LayoutPreset::factory()->create([
        'site_id' => $site->getKey(),
        'snapshot' => [
            'containers' => [
                'main' => [
                    'blocks' => [
                        ['block_key' => 'hero'],
                    ],
                ],
            ],
        ],
    ]);

    ApplyLayoutPresetAction::run($preset, $layout, $site, persist: true);

    expect($layout->fresh()->containers)->toHaveKey('main');
});

it('can apply a site preset to a global layout', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->create([
        'site_id' => null,
        'containers' => [],
    ]);
    $preset = LayoutPreset::factory()->create([
        'site_id' => $site->getKey(),
        'snapshot' => [
            'containers' => [
                'main' => [
                    'blocks' => [
                        ['block_key' => 'hero'],
                    ],
                ],
            ],
        ],
    ]);

    $updatedLayout = ApplyLayoutPresetAction::run($preset, $layout, $site);

    expect($updatedLayout->containers)->toHaveKey('main');
});

it('strips unsafe metadata when applying stored preset snapshots', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create(['containers' => []]);
    $preset = LayoutPreset::factory()->create([
        'site_id' => $site->getKey(),
        'snapshot' => [
            'containers' => [
                'main' => [
                    'blocks' => [
                        [
                            'block_key' => 'hero',
                            'meta' => [
                                'admin_schema' => ['secret' => true],
                                'block_settings' => [
                                    'anchor_id' => 'Package pricing',
                                    'cta_url' => 'https://example.test/admin/signed-editor-url?signature=secret',
                                    'signed_url' => 'https://example.test/admin/signed',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $updatedLayout = ApplyLayoutPresetAction::run($preset, $layout, $site);
    $encodedContainers = json_encode($updatedLayout->containers, JSON_THROW_ON_ERROR);

    expect($encodedContainers)
        ->toContain('package-pricing')
        ->not->toContain('admin_schema')
        ->not->toContain('signed_url')
        ->not->toContain('https://example.test/admin/signed-editor-url?signature=secret');
});

it('uniques pasted preset anchors against the current builder state', function (): void {
    $state = new LayoutBuilderStateData(
        containers: [
            'main' => [
                'blocks' => [
                    [
                        'block_key' => 'hero',
                        'meta' => ['block_settings' => ['anchor_id' => 'feature-grid']],
                    ],
                ],
            ],
        ],
        assets: [],
        originalAssets: [],
        selectedRecords: [],
    );

    $fragment = new LayoutFragmentData(
        sourceContainerKey: 'preset',
        sourceBlockIndex: null,
        container: [
            'blocks' => [
                [
                    'block_key' => 'cards',
                    'meta' => ['block_settings' => ['anchor_id' => 'Feature Grid']],
                ],
            ],
        ],
        block: null,
    );

    $result = PasteLayoutFragmentAction::run($state, $fragment, 'main');

    expect($result->state->containers['preset-copy']['blocks'][0]['meta']['block_settings']['anchor_id'])
        ->toBe('feature-grid-2');
});

it('refuses to apply presets across sites', function (): void {
    $sourceSite = Site::factory()->create();
    $targetSite = Site::factory()->create();
    $layout = Layout::factory()->site($targetSite)->create(['containers' => []]);
    $preset = LayoutPreset::factory()->create(['site_id' => $sourceSite->getKey()]);

    ApplyLayoutPresetAction::run($preset, $layout, $targetSite);
})->throws(LogicException::class, 'same site');
