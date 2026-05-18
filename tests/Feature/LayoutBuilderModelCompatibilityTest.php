<?php

declare(strict_types=1);

use Capell\Core\Models\Layout as CoreLayout;
use Capell\LayoutBuilder\Models\Block;
use Capell\LayoutBuilder\Models\BlockAsset;
use Capell\LayoutBuilder\Models\Layout;

it('keeps layouts on the core layout model and uses package-owned block models', function (): void {
    expect(Layout::class)->toExtend(CoreLayout::class)
        ->and(Block::class)->not->toExtend(CoreLayout::class)
        ->and(BlockAsset::class)->not->toExtend(CoreLayout::class);
});

it('keeps the existing layout builder table names', function (): void {
    expect((new Layout)->getTable())->toBe('layouts')
        ->and((new Block)->getTable())->toBe('blocks')
        ->and((new BlockAsset)->getTable())->toBe('block_assets');
});
