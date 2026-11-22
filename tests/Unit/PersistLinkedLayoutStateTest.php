<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Actions\PersistLayoutBuilderStateAction;
use Capell\LayoutBuilder\Data\LayoutPresetLinkData;
use Capell\LayoutBuilder\Enums\LayoutPresetMode;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Carbon\CarbonImmutable;

it('rejects a stale editor before touching saved containers or assets', function (): void {
    $current = ['main' => ['widgets' => ['current-widget']]];
    $layout = Layout::factory()->createOne(['containers' => $current, 'updated_at' => '2026-11-18 12:00:00']);
    $persistAssets = false;

    expect(function () use ($layout, &$persistAssets): void {
        PersistLayoutBuilderStateAction::run(
            layout: $layout,
            page: null,
            containers: ['main' => ['widgets' => ['outdated-widget']]],
            persistWidgetAssets: function () use (&$persistAssets): void {
                $persistAssets = true;
            },
            expectedUpdatedAt: CarbonImmutable::parse('2026-11-18 11:00:00'),
        );
    })->toThrow(LogicException::class, 'The layout changed in another editor.');

    expect($layout->refresh()->containers)->toBe($current)
        ->and($persistAssets)->toBeFalse();
});

it('updates just the linked preset item and increments its revision when saving source containers', function (): void {
    $layout = Layout::factory()->createOne();
    $unrelated = ['id' => 'sidebar-source', 'container' => ['widgets' => ['sidebar-widget']]];
    $preset = LayoutPreset::factory()->createOne([
        'mode' => LayoutPresetMode::Linked,
        'revision' => 3,
        'snapshot' => ['items' => [
            ['id' => 'main-source', 'container' => ['widgets' => ['old-widget']]],
            $unrelated,
        ]],
    ]);
    $containers = ['main' => ['widgets' => ['replacement-widget']]];

    PersistLayoutBuilderStateAction::run(
        layout: $layout,
        page: null,
        containers: $containers,
        persistWidgetAssets: static function (): void {},
        linkedPreset: $preset,
        linkedPresetLink: new LayoutPresetLinkData($preset->id, 'main-source', $preset->key),
        linkedContainerKey: 'main',
    );

    expect($layout->refresh()->containers)->toBe($containers)
        ->and($preset->refresh()->revision)->toBe(4)
        ->and($preset->snapshot['items'])->toBe([
            ['id' => 'main-source', 'container' => $containers['main']],
            $unrelated,
        ]);
});
