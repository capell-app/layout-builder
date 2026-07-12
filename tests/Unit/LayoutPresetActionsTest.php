<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Actions\ApplyLayoutPresetAction;
use Capell\LayoutBuilder\Actions\ApplyStarterLayoutPresetAction;
use Capell\LayoutBuilder\Actions\Mutations\PasteLayoutFragmentAction;
use Capell\LayoutBuilder\Actions\SaveLayoutPresetAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutFragmentData;
use Capell\LayoutBuilder\Models\LayoutPreset;

/**
 * @param  array<string, mixed>  $containers
 * @return array<string, mixed>
 */
function layoutPresetContainer(array $containers, string $containerKey): array
{
    $container = $containers[$containerKey] ?? [];

    return is_array($container) ? $container : [];
}

/**
 * @param  array<string, mixed>  $container
 * @return array<string, mixed>
 */
function layoutPresetMeta(array $container): array
{
    $meta = $container['meta'] ?? [];

    return is_array($meta) ? $meta : [];
}

/**
 * @return array<string, mixed>
 */
function layoutPresetArray(mixed $value): array
{
    return is_array($value) ? $value : [];
}

/**
 * @param  array<string, mixed>  $container
 * @return list<array<string, mixed>>
 */
function layoutPresetWidgets(array $container): array
{
    $widgets = $container['widgets'] ?? [];

    if (! is_array($widgets)) {
        return [];
    }

    $normalizedWidgets = [];

    foreach ($widgets as $widget) {
        if (is_array($widget)) {
            $normalizedWidgets[] = $widget;
        }
    }

    return $normalizedWidgets;
}

it('persists site-scoped layout presets as layout-only snapshots by default', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    [
                        'widget_key' => 'hero',
                        'occurrence' => 1,
                        'meta' => [
                            'widget_variant' => 'split-media',
                            'widget_settings' => [
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
        ->and($preset->snapshot['containers']['main']['widgets'][0])->toHaveKeys(['widget_key', 'occurrence', 'meta'])
        ->and($preset->snapshot['containers']['main']['widgets'][0])->not->toHaveKey('editor_only')
        ->and(json_encode($preset->snapshot, JSON_THROW_ON_ERROR))->not->toContain('signed_url')
        ->and(json_encode($preset->snapshot, JSON_THROW_ON_ERROR))->not->toContain('admin_schema');
});

it('can persist a current editor container snapshot without reading stale layout containers', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['widgets' => []],
        ],
    ]);

    $preset = SaveLayoutPresetAction::run(
        layout: $layout,
        site: $site,
        name: 'Edited hero',
        category: 'hero',
        containers: [
            'hero' => [
                'widgets' => [
                    [
                        'widget_key' => 'hero',
                        'occurrence' => 1,
                        'meta' => ['widget_settings' => ['anchor_id' => 'Edited']],
                    ],
                ],
            ],
        ],
    );

    expect($preset->category)->toBe('hero')
        ->and($preset->snapshot['containers'])->toHaveKey('hero')
        ->and($preset->snapshot['containers'])->not->toHaveKey('main')
        ->and($preset->snapshot['containers']['hero']['widgets'][0]['meta']['widget_settings']['anchor_id'])->toBe('Edited');
});

it('can save a site-scoped preset from a global layout', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->create([
        'site_id' => null,
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'hero'],
                ],
            ],
        ],
    ]);

    $preset = SaveLayoutPresetAction::run($layout, $site, 'Shared hero');

    expect($preset->site_id)->toBe($site->getKey())
        ->and($preset->snapshot['containers'])->toHaveKey('main');
});

it('normalizes shorthand layout widgets when saving presets', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    'hero',
                    [
                        'widget_key' => 'cards',
                        'occurrence' => 2,
                    ],
                ],
            ],
        ],
    ]);

    $preset = SaveLayoutPresetAction::run($layout, $site, 'Shorthand layout');

    expect($preset->snapshot['containers']['main']['widgets'])->toBe([
        [
            'widget_key' => 'hero',
            'occurrence' => 1,
            'meta' => [],
        ],
        [
            'widget_key' => 'cards',
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
                'widgets' => [
                    [
                        'widget_key' => 'hero',
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
                            'widget_settings' => ['anchor_id' => 'Hero'],
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
                'widgets' => [
                    [
                        'widget_key' => 'hero',
                        'meta' => [
                            'widget_key' => 'package_pricing',
                            'widget_variant' => 'schema-markup',
                            'widget_settings' => [
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
                    'widgets' => [
                        [
                            'widget_key' => 'hero',
                            'meta' => ['widget_settings' => ['anchor_id' => 'Feature Grid']],
                        ],
                        [
                            'widget_key' => 'cards',
                            'meta' => ['widget_settings' => ['anchor_id' => 'Feature Grid']],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $updatedLayout = ApplyLayoutPresetAction::run($preset, $layout, $site);

    expect($updatedLayout->containers['main']['widgets'][0]['meta']['widget_settings']['anchor_id'])->toBe('feature-grid')
        ->and($updatedLayout->containers['main']['widgets'][1]['meta']['widget_settings']['anchor_id'])->toBe('feature-grid-2')
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
                    'widgets' => [
                        ['widget_key' => 'hero'],
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
                    'widgets' => [
                        ['widget_key' => 'hero'],
                    ],
                ],
            ],
        ],
    ]);

    $updatedLayout = ApplyLayoutPresetAction::run($preset, $layout, $site);

    expect($updatedLayout->containers)->toHaveKey('main');
});

it('materializes a registered starter layout preset into a complete builder state', function (): void {
    $result = ApplyStarterLayoutPresetAction::run('landing');
    $mainContainer = layoutPresetContainer($result->state->containers, 'main');

    expect($result->state->containers)->toHaveKey('main')
        ->and(layoutPresetMeta($mainContainer)['colspan'] ?? null)->toBe(12)
        ->and(capell_test_collect(layoutPresetWidgets($mainContainer))->pluck('widget_key')->all())->toBe([
            'hero',
            'proof',
            'features',
            'cta',
        ])
        ->and($result->state->assets['main'])->toHaveCount(4)
        ->and($result->state->selectedRecords['main'])->toHaveCount(4);
});

it('applies starter layout responsive column hints for sidebar presets', function (): void {
    $result = ApplyStarterLayoutPresetAction::run('sidebar-main-footer');
    $sidebarContainer = layoutPresetContainer($result->state->containers, 'sidebar');
    $mainContainer = layoutPresetContainer($result->state->containers, 'main');
    $footerContainer = layoutPresetContainer($result->state->containers, 'footer');
    $sidebarMeta = layoutPresetMeta($sidebarContainer);
    $sidebarResponsive = layoutPresetArray($sidebarMeta['responsive'] ?? null);
    $sidebarTabletResponsive = layoutPresetArray($sidebarResponsive['tablet'] ?? null);

    expect($sidebarMeta['colspan'] ?? null)->toBe(4)
        ->and($sidebarTabletResponsive['colspan'] ?? null)->toBe(12)
        ->and(layoutPresetMeta($mainContainer)['colspan'] ?? null)->toBe(8)
        ->and(layoutPresetMeta($footerContainer)['colspan'] ?? null)->toBe(12)
        ->and(layoutPresetWidgets($sidebarContainer)[0]['widget_key'] ?? null)->toBe('hero')
        ->and(layoutPresetWidgets($mainContainer)[0]['widget_key'] ?? null)->toBe('content')
        ->and(layoutPresetWidgets($footerContainer)[0]['widget_key'] ?? null)->toBe('signup-footer');
});

it('rejects unknown starter layout preset keys', function (): void {
    ApplyStarterLayoutPresetAction::run('missing-layout');
})->throws(InvalidArgumentException::class, 'Unknown starter layout preset');

it('strips unsafe metadata when applying stored preset snapshots', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create(['containers' => []]);
    $preset = LayoutPreset::factory()->create([
        'site_id' => $site->getKey(),
        'snapshot' => [
            'containers' => [
                'main' => [
                    'widgets' => [
                        [
                            'widget_key' => 'hero',
                            'meta' => [
                                'admin_schema' => ['secret' => true],
                                'widget_settings' => [
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
                'widgets' => [
                    [
                        'widget_key' => 'hero',
                        'meta' => ['widget_settings' => ['anchor_id' => 'feature-grid']],
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
        sourceWidgetIndex: null,
        container: [
            'widgets' => [
                [
                    'widget_key' => 'cards',
                    'meta' => ['widget_settings' => ['anchor_id' => 'Feature Grid']],
                ],
            ],
        ],
        widget: null,
    );

    $result = PasteLayoutFragmentAction::run($state, $fragment, 'main');

    expect($result->state->containers['preset-copy']['widgets'][0]['meta']['widget_settings']['anchor_id'])
        ->toBe('feature-grid-2');
});

it('refuses to apply presets across sites', function (): void {
    $sourceSite = Site::factory()->create();
    $targetSite = Site::factory()->create();
    $layout = Layout::factory()->site($targetSite)->create(['containers' => []]);
    $preset = LayoutPreset::factory()->create(['site_id' => $sourceSite->getKey()]);

    ApplyLayoutPresetAction::run($preset, $layout, $targetSite);
})->throws(LogicException::class, 'same site');
