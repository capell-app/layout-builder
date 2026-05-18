<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\CreateHeroBlockAction;
use Capell\LayoutBuilder\Models\Block;

it('persists the hero component as a string value without an encoded enum meta payload', function (): void {
    $block = CreateHeroBlockAction::run();

    $block->refresh();

    $meta = json_decode((string) $block->getRawOriginal('meta'), true, flags: JSON_THROW_ON_ERROR);

    expect($block)->toBeInstanceOf(Block::class)
        ->and($block->component)->toBe('capell.block.hero')
        ->and($block->component)->toBeString()
        ->and($meta)->not->toHaveKey('component');
});
