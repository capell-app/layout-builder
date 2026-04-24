<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Navigation\Models\Navigation;

it('belongs to a language', function (): void {
    $language = Language::factory()->create();
    $navigation = Navigation::factory()->create(['language_id' => $language->id]);

    expect($navigation->language)->toBeInstanceOf(Language::class)
        ->and($navigation->language->id)->toBe($language->id);
});

it('belongs to a site', function (): void {
    $site = Site::factory()->create();
    $navigation = Navigation::factory()->create(['site_id' => $site->id]);

    expect($navigation->site)->toBeInstanceOf(Site::class)
        ->and($navigation->site->id)->toBe($site->id);
});
