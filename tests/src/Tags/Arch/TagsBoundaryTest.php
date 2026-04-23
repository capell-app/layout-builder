<?php

declare(strict_types=1);

use Capell\Tags\Enums\ModelEnum;
use Capell\Tags\Enums\ResourceEnum;
use Capell\Tags\Enums\TagTypeEnum;
use Capell\Tags\Filament\Components\Forms\TagsInput;
use Capell\Tags\Filament\Resources\Tags\TagResource;
use Capell\Tags\Models\Concerns\HasTags;
use Capell\Tags\Models\Tag;
use Capell\Tags\Models\Taggable;
use Capell\Tags\Providers\TagsServiceProvider;

it('Tag class exists under Capell\\Tags namespace', function (): void {
    expect(class_exists(Tag::class))->toBeTrue();
});

it('Taggable class exists under Capell\\Tags namespace', function (): void {
    expect(class_exists(Taggable::class))->toBeTrue();
});

it('HasTags trait exists under Capell\\Tags namespace', function (): void {
    expect(trait_exists(HasTags::class))->toBeTrue();
});

it('TagTypeEnum exists under Capell\\Tags namespace', function (): void {
    expect(enum_exists(TagTypeEnum::class))->toBeTrue();
});

it('ModelEnum exists under Capell\\Tags namespace', function (): void {
    expect(enum_exists(ModelEnum::class))->toBeTrue();
});

it('ResourceEnum exists under Capell\\Tags namespace', function (): void {
    expect(enum_exists(ResourceEnum::class))->toBeTrue();
});

it('TagResource class exists under Capell\\Tags namespace', function (): void {
    expect(class_exists(TagResource::class))->toBeTrue();
});

it('TagsInput component exists under Capell\\Tags namespace', function (): void {
    expect(class_exists(TagsInput::class))->toBeTrue();
});

it('TagsServiceProvider exists under Capell\\Tags namespace', function (): void {
    expect(class_exists(TagsServiceProvider::class))->toBeTrue();
});

it('Tag class no longer exists under Capell\\Blog namespace', function (): void {
    expect(class_exists('Capell\\Blog\\Models\\Tag'))->toBeFalse();
});

it('Taggable class no longer exists under Capell\\Blog namespace', function (): void {
    expect(class_exists('Capell\\Blog\\Models\\Taggable'))->toBeFalse();
});

it('HasTags trait no longer exists under Capell\\Blog namespace', function (): void {
    expect(trait_exists('Capell\\Blog\\Models\\Concerns\\HasTags'))->toBeFalse();
});

it('TagTypeEnum no longer exists under Capell\\Blog namespace', function (): void {
    expect(enum_exists('Capell\\Blog\\Enums\\TagTypeEnum'))->toBeFalse();
});

it('TagResource no longer exists under Capell\\Blog namespace', function (): void {
    expect(class_exists('Capell\\Blog\\Filament\\Resources\\Tags\\TagResource'))->toBeFalse();
});
