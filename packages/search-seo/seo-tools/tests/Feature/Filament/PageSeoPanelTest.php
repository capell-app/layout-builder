<?php

declare(strict_types=1);

use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\SeoTools\Filament\Components\Forms\Page\PageSeoPanel;
use Capell\SeoTools\Filament\Extenders\Page\PageSeoPanelSchemaExtender;
use Filament\Schemas\Schema;

it('registers the page SEO panel schema extender', function (): void {
    $extenders = collect(app()->tagged(PageSchemaExtender::TAG));

    expect($extenders->contains(
        fn (PageSchemaExtender $extender): bool => $extender instanceof PageSeoPanelSchemaExtender,
    ))->toBeTrue();
});

it('adds the page SEO panel after search meta', function (): void {
    $extender = resolve(PageSeoPanelSchemaExtender::class);
    $components = $extender->extendTranslationComponentsForHook(
        Schema::make(),
        PageTranslationSchemaHookEnum::AfterSearchMeta,
    );

    expect($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(PageSeoPanel::class);
});
