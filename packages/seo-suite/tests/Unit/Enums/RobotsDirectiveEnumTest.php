<?php

declare(strict_types=1);

use Capell\SeoSuite\Enums\RobotsDirectiveEnum;

it('exposes labels and descriptions for every robots directive option', function (): void {
    expect(RobotsDirectiveEnum::cases())->toHaveCount(7);

    foreach (RobotsDirectiveEnum::cases() as $directive) {
        expect($directive->getLabel())->toBeString()
            ->and($directive->getLabel())->not->toBe('')
            ->and($directive->getDescription())->toBeString()
            ->and($directive->getDescription())->not->toBe('');
    }
});

it('keeps persisted robots directive values stable', function (): void {
    expect(collect(RobotsDirectiveEnum::cases())->map->value->all())->toBe([
        'noindex',
        'nofollow',
        'noarchive',
        'nosnippet',
        'max-snippet:-1',
        'max-image-preview:large',
        'max-video-preview:-1',
    ]);
});
