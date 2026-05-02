<?php

declare(strict_types=1);

use Capell\Tags\Enums\TagTypeEnum;
use Capell\Tags\Models\Tag;
use Capell\Tags\Providers\TagsServiceProvider;

it('Tag class exists', function (): void {
    expect(class_exists(Tag::class))->toBeTrue();
});

it('TagsServiceProvider class exists', function (): void {
    expect(class_exists(TagsServiceProvider::class))->toBeTrue();
});

it('repairs stale published tag model config', function (): void {
    config(['tags.tag_model' => 'Capell\\Blog\\Models\\Tag']);

    (new TagsServiceProvider(app()))->registeringPackage();

    expect(config('tags.tag_model'))->toBe(Tag::class);
});

it('TagTypeEnum is a backed enum with expected cases', function (): void {
    expect(enum_exists(TagTypeEnum::class))->toBeTrue();

    $cases = TagTypeEnum::cases();
    $caseNames = array_map(fn (TagTypeEnum $case) => $case->name, $cases);

    expect($caseNames)->toContain('Article');
    expect($caseNames)->toContain('Content');
    expect($caseNames)->toContain('Page');
});
