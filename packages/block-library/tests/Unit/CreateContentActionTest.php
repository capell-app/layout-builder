<?php

declare(strict_types=1);

use Capell\BlockLibrary\Actions\CreateContentAction;
use Capell\BlockLibrary\Enums\LayoutTypeEnum;
use Capell\BlockLibrary\Models\ContentBlock;
use Capell\Core\Models\Language;
use Capell\Core\Models\Type;

it('creates a content block using the first translation title as the fallback name', function (): void {
    $language = Language::factory()->english()->create();
    $type = Type::query()->create([
        'name' => 'Content',
        'key' => 'content',
        'type' => LayoutTypeEnum::ContentBlock->value,
        'status' => true,
    ]);

    $content = CreateContentAction::run([
        'type_id' => $type->getKey(),
        'meta' => ['color' => 'primary'],
        'translations' => [
            [
                'language_id' => $language->getKey(),
                'title' => 'Reusable announcement',
                'content' => '<p>Publish once and reuse across pages.</p>',
            ],
        ],
    ]);

    expect($content)
        ->toBeInstanceOf(ContentBlock::class)
        ->and($content->name)->toBe('Reusable announcement')
        ->and($content->meta)->toBe(['color' => 'primary'])
        ->and($content->translations()->count())->toBe(1)
        ->and($content->translations()->first()->title)->toBe('Reusable announcement');
});

it('preserves an explicit content block name when translations are supplied', function (): void {
    $language = Language::factory()->english()->create();
    $type = Type::query()->create([
        'name' => 'Content',
        'key' => 'content',
        'type' => LayoutTypeEnum::ContentBlock->value,
        'status' => true,
    ]);

    $content = CreateContentAction::run([
        'name' => 'Internal admin name',
        'type_id' => $type->getKey(),
        'translations' => [
            [
                'language_id' => $language->getKey(),
                'title' => 'Public title',
                'content' => '<p>Public copy.</p>',
            ],
        ],
    ]);

    expect($content->name)->toBe('Internal admin name')
        ->and($content->translations()->first()->title)->toBe('Public title');
});
