<?php

declare(strict_types=1);

use Capell\Blog\Listeners\ArticleTranslationSavedListener;
use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Illuminate\Database\Eloquent\Relations\Relation;

it('ignores non article translations when the article morph map is unavailable', function (): void {
    Relation::morphMap(['page' => Page::class], merge: false);

    $translation = new Translation;
    $translation->translatable_type = 'page';

    (new ArticleTranslationSavedListener)($translation);

    expect(true)->toBeTrue();
});
