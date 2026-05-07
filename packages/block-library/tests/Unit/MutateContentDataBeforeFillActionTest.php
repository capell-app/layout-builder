<?php

declare(strict_types=1);

use Capell\BlockLibrary\Actions\MutateContentDataBeforeFillAction;
use Capell\BlockLibrary\Enums\LayoutTypeEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Illuminate\Support\Facades\Cache;

it('prepares content block form state from the requested type and default site languages', function (): void {
    Cache::flush();

    $language = Language::factory()->english()->create();
    $siteType = Type::query()->create([
        'name' => 'Site',
        'key' => 'site',
        'type' => 'site',
        'status' => true,
    ]);
    $contentType = Type::query()->create([
        'name' => 'Accordion',
        'key' => 'accordion',
        'type' => LayoutTypeEnum::ContentBlock->value,
        'status' => true,
    ]);
    $site = Site::query()->create([
        'name' => 'Default site',
        'type_id' => $siteType->getKey(),
        'theme_id' => 1,
        'language_id' => $language->getKey(),
        'default' => true,
        'status' => true,
    ]);
    $site->translations()->create([
        'language_id' => $language->getKey(),
        'title' => 'Default site',
    ]);

    $state = MutateContentDataBeforeFillAction::run([
        'type_id' => $contentType->getKey(),
    ]);

    expect($state['type_id'])->toBe($contentType->getKey())
        ->and($state['translations'])->toHaveCount(1)
        ->and(array_values($state['translations'])[0])->toBe([
            'language_id' => $language->getKey(),
        ]);
});
