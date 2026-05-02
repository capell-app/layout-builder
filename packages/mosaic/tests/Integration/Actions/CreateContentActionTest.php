<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Mosaic\Actions\CreateContentAction;
use Capell\Mosaic\Actions\InstallPackageAction;
use Capell\Mosaic\Models\Section;

beforeEach(function (): void {
    InstallPackageAction::run();
});

it('creates a section with the provided attributes', function (): void {
    $section = CreateContentAction::run(['name' => 'My Section']);

    expect($section)->toBeInstanceOf(Section::class)
        ->and($section->name)->toBe('My Section')
        ->and($section->getKey())->not()->toBeNull();
});

it('derives the name from the first translation when no name is given', function (): void {
    $language = Language::factory()->create();

    $section = CreateContentAction::run([
        'translations' => [
            ['language_id' => $language->id, 'title' => 'Title From Translation', 'content' => null],
        ],
    ]);

    expect($section->name)->toBe('Title From Translation');
});

it('creates translations on the section when translations are provided', function (): void {
    $languageA = Language::factory()->create();
    $languageB = Language::factory()->create();

    $section = CreateContentAction::run([
        'name' => 'Named Section',
        'translations' => [
            ['language_id' => $languageA->id, 'title' => 'EN Title', 'content' => '<p>Hello</p>'],
            ['language_id' => $languageB->id, 'title' => 'FR Title', 'content' => '<p>Bonjour</p>'],
        ],
    ]);

    expect($section->translations)->toHaveCount(2)
        ->and($section->translations->pluck('title')->all())
        ->toEqualCanonicalizing(['EN Title', 'FR Title']);
});

it('creates a section with no translations when none are provided', function (): void {
    $section = CreateContentAction::run(['name' => 'Plain Section']);

    expect($section->translations)->toBeEmpty();
});
