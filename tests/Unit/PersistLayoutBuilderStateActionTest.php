<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Actions\PersistLayoutBuilderStateAction;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewMetaKey;
use Illuminate\Support\Facades\Storage;

it('persists layout builder containers, page assignment, widget assets, and preview invalidation', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('layout-previews/old.png', 'old');

    $layout = Layout::factory()->create([
        'admin' => [
            LayoutPreviewMetaKey::IMAGE => 'layout-previews/old.png',
            LayoutPreviewMetaKey::SIGNATURE => 'old-signature',
        ],
        'containers' => [
            'main' => ['widgets' => []],
        ],
    ]);
    $page = Page::factory()->create(['layout_id' => null]);
    $persistedWidgetAssets = false;
    $containers = [
        'main' => [
            'widgets' => [
                ['key' => 'hero', 'widget' => ['type' => 'hero']],
            ],
        ],
    ];

    PersistLayoutBuilderStateAction::run(
        layout: $layout,
        page: $page,
        containers: $containers,
        persistWidgetAssets: function () use (&$persistedWidgetAssets): void {
            $persistedWidgetAssets = true;
        },
    );

    $layout->refresh();
    $page->refresh();

    expect($layout->containers)->toBe($containers)
        ->and($page->layout_id)->toBe($layout->getKey())
        ->and($persistedWidgetAssets)->toBeTrue()
        ->and($layout->admin[LayoutPreviewMetaKey::SIGNATURE] ?? null)->not->toBe('old-signature');

    Storage::disk('public')->assertMissing('layout-previews/old.png');
});

it('rolls back layout and page updates when widget asset persistence fails', function (): void {
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => ['widgets' => []],
        ],
    ]);
    $page = Page::factory()->create(['layout_id' => null]);
    $originalPageLayoutId = $page->layout_id;

    expect(function () use ($layout, $page): void {
        PersistLayoutBuilderStateAction::run(
            layout: $layout,
            page: $page,
            containers: [
                'main' => [
                    'widgets' => [
                        ['key' => 'hero', 'widget' => ['type' => 'hero']],
                    ],
                ],
            ],
            persistWidgetAssets: static function (): void {
                throw new RuntimeException('Widget asset persistence failed.');
            },
        );
    })->toThrow(RuntimeException::class, 'Widget asset persistence failed.');

    expect($layout->refresh()->containers)->toBe([
        'main' => ['widgets' => []],
    ])
        ->and($page->refresh()->layout_id)->toBe($originalPageLayoutId);
});
