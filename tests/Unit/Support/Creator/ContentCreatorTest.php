<?php

declare(strict_types=1);

use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Support\Creator\ContentCreator;
use Illuminate\Support\Collection;

it('falls back to english demo content when a requested translation is missing', function (): void {
    Blueprint::factory()->create(['type' => 'section', 'default' => true]);
    $german = Language::factory()->create(['code' => 'de']);
    $content = new ContentCreator()->createContent([
        'name' => 'Fallback section',
        'translations' => [
            'en' => ['title' => 'English title', 'content' => '<p>English content</p>'],
            'fr' => ['title' => 'French title', 'content' => '<p>French content</p>'],
        ],
    ], null, new Collection([$german]));
    $translation = Translation::query()->whereMorphedTo('translatable', $content)->where('language_id', $german->getKey())->sole();

    expect($translation->title)->toBe('English title')
        ->and($translation->content)->toBe('<p>English content</p>');
});
