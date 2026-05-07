<?php

declare(strict_types=1);

use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\Core\Models\Page;
use Capell\SeoSuite\Enums\RobotsDirectiveEnum;
use Capell\SeoSuite\Filament\Extenders\Page\RobotsDirectiveSchemaExtender;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Schema;

it('adds robots directive settings with enum-backed descriptions', function (): void {
    $extender = resolve(RobotsDirectiveSchemaExtender::class);

    $components = $extender->extendSettingsTabComponents();
    $robotsField = $components[0] ?? null;

    expect($components)->toHaveCount(1)
        ->and($robotsField)->toBeInstanceOf(CheckboxList::class)
        ->and($robotsField->getName())->toBe('robots')
        ->and($robotsField->getOptions())->toBe(
            collect(RobotsDirectiveEnum::cases())
                ->mapWithKeys(fn (RobotsDirectiveEnum $directive): array => [$directive->value => $directive->getLabel()])
                ->all(),
        );
});

it('leaves unrelated page schema extension points unchanged', function (): void {
    $extender = resolve(RobotsDirectiveSchemaExtender::class);
    $page = Page::factory()->create();
    $relationManagers = ['existing'];
    $tabs = ['settings'];

    expect($extender->extendTranslationComponentsForHook(Schema::make(), PageTranslationSchemaHookEnum::AfterSearchMeta))
        ->toBe([])
        ->and($extender->extendRelationManagers($page, $relationManagers))->toBe($relationManagers)
        ->and($extender->extendTabs(Schema::make(), $tabs))->toBe($tabs);
});
