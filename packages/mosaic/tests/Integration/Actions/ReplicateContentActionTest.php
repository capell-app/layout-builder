<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Mosaic\Actions\ReplicateContentAction;
use Capell\Mosaic\Models\Section;

it('creates a replica of the section', function (): void {
    $original = Section::factory()->create(['name' => 'Original Section']);

    $replica = ReplicateContentAction::run($original);

    expect($replica->getKey())->not()->toBe($original->getKey())
        ->and($replica->name)->toBe('Original Section')
        ->and(Section::query()->count())->toBe(2);
});

it('overrides attributes on the replica when data is provided', function (): void {
    $original = Section::factory()->create(['name' => 'Original']);

    $replica = ReplicateContentAction::run($original, ['name' => 'Renamed Replica']);

    expect($replica->name)->toBe('Renamed Replica')
        ->and($original->fresh()->name)->toBe('Original');
});

it('creates translations on the replica when translations are provided', function (): void {
    $language = Language::factory()->create();
    $original = Section::factory()->create();

    $replica = ReplicateContentAction::run($original, [
        'translations' => [
            ['language_id' => $language->id, 'title' => 'Replica Title', 'content' => 'Replica content'],
        ],
    ]);

    expect($replica->translations)->toHaveCount(1)
        ->and($replica->translations->first()->title)->toBe('Replica Title')
        ->and($replica->translations->first()->language_id)->toBe($language->id);
});

it('does not copy translations from the original when none are provided', function (): void {
    $language = Language::factory()->create();
    $original = Section::factory()->create();
    $original->translations()->create([
        'language_id' => $language->id,
        'title' => 'Original Title',
        'content' => null,
    ]);

    $replica = ReplicateContentAction::run($original);

    expect($replica->translations)->toBeEmpty();
});
