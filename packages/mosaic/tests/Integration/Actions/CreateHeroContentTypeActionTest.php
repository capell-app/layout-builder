<?php

declare(strict_types=1);

use Capell\Core\Models\Type;
use Capell\Mosaic\Actions\CreateHeroContentTypeAction;
use Capell\Mosaic\Enums\LayoutTypeEnum;

it('creates a hero content type with the correct key and type', function (): void {
    $type = CreateHeroContentTypeAction::run();

    expect($type)->toBeInstanceOf(Type::class)
        ->and($type->key)->toBe('hero')
        ->and($type->type)->toBe(LayoutTypeEnum::Section);
});

it('is idempotent and returns the same record on repeated calls', function (): void {
    $first = CreateHeroContentTypeAction::run();
    $second = CreateHeroContentTypeAction::run();

    expect($second->getKey())->toBe($first->getKey())
        ->and(Type::query()->where('key', 'hero')->count())->toBe(1);
});
