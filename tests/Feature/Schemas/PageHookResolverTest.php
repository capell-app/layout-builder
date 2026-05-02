<?php

declare(strict_types=1);

namespace Capell\Mosaic\Tests\Feature\Schemas;

use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\Admin\Support\Schemas\PageTranslationSchemaHookResolver;
use Capell\Mosaic\Filament\Extenders\Page\HeroPageSchemaExtender;
use Filament\Schemas\Schema;

it('resolves page translation hook components from tagged extenders', function (): void {
    // Register the hero extender into the container under the Page tag
    $this->app->tag([HeroPageSchemaExtender::class], PageSchemaExtender::TAG);

    $resolver = $this->app->make(PageTranslationSchemaHookResolver::class);

    $mockSchema = Schema::make();

    $components = $resolver->resolve($mockSchema, PageTranslationSchemaHookEnum::AfterTitle);

    expect($components)->not->toBeEmpty();
});
