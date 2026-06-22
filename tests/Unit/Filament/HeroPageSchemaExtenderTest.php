<?php

declare(strict_types=1);

use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\Admin\Testing\Filament\ReadsRawSchemaComponents;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Filament\Components\Forms\Page\HeroEditor;
use Capell\LayoutBuilder\Filament\Extenders\Page\HeroPageSchemaExtender;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Filament\Schemas\Schema;

it('contributes the hero editor as a full width section after the translated title', function (): void {
    $extender = new HeroPageSchemaExtender;

    $components = $extender->extendTranslationComponentsForHook(Schema::make(), PageTranslationSchemaHookEnum::AfterTitle);

    expect($components)
        ->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(HeroEditor::class)
        ->and($extender->extendTranslationComponentsForHook(Schema::make(), PageTranslationSchemaHookEnum::AfterContentEditor))
        ->toBe([]);

    $editorComponents = ReadsRawSchemaComponents::childComponents($components[0]);

    expect($editorComponents)
        ->toHaveCount(1)
        ->and(layoutBuilderFilamentObjectName($editorComponents[0]))->toBe('hero');
});

it('hides the translated hero editor when page hero widget assets exist', function (): void {
    $page = Page::factory()->createOne();

    expect(layoutBuilderMountedHeroEditor($page)->isVisible())->toBeTrue();

    $pageWithWidgetHero = Page::factory()->createOne();
    $heroWidget = Widget::factory()->createOne(['key' => 'hero-banner']);

    WidgetAsset::factory()
        ->widget($heroWidget)
        ->page($pageWithWidgetHero)
        ->createOne();

    expect(layoutBuilderMountedHeroEditor($pageWithWidgetHero)->isVisible())->toBeFalse();
});

function layoutBuilderMountedHeroEditor(Page $page): HeroEditor
{
    $schema = Schema::make()
        ->record($page)
        ->components([HeroEditor::make()]);

    $component = $schema->getComponents(withHidden: true)[0] ?? null;

    expect($component)->toBeInstanceOf(HeroEditor::class);
    assert($component instanceof HeroEditor);

    return $component;
}

function layoutBuilderFilamentObjectName(object $object): ?string
{
    if (! method_exists($object, 'getName')) {
        return null;
    }

    $name = $object->getName();

    return is_string($name) ? $name : null;
}
