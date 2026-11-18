<?php

declare(strict_types=1);

use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\Admin\Settings\AdminSettings;
use Capell\Admin\Testing\Filament\ReadsRawSchemaComponents;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Filament\Components\Forms\Page\HeroEditor;
use Capell\LayoutBuilder\Filament\Extenders\Page\HeroPageSchemaExtender;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
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
    $editorComponent = $editorComponents[0] ?? null;

    throw_unless(is_object($editorComponent), RuntimeException::class, 'Expected the hero editor child component to be an object.');

    expect($editorComponents)
        ->toHaveCount(1)
        ->and(layoutBuilderFilamentObjectName($editorComponent))->toBe('hero');
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

it('labels the hero as the top of the page and offers guidance while helper tips are on', function (): void {
    $page = Page::factory()->createOne();
    $editor = layoutBuilderHeroContentField($page);

    expect($editor->getLabel())->toBe(__('capell-layout-builder::form.hero'))
        ->and(collect($editor->getHintActions())->filter(fn (Action $action): bool => $action->isVisible())->map(fn (Action $action): ?string => $action->getName())->values()->all())
        ->toBe(['heroGuidance']);
});

it('drops the hero hint and guidance when helper tips are turned off', function (): void {
    $settings = resolve(AdminSettings::class);
    $settings->show_helper_tooltips = false;
    $settings->save();

    $editor = layoutBuilderHeroContentField(Page::factory()->createOne());

    expect(collect($editor->getHintActions())->filter(fn (Action $action): bool => $action->isVisible())->all())
        ->toBe([]);
});

function layoutBuilderHeroContentField(Page $page): Field
{
    $field = ReadsRawSchemaComponents::childComponents(layoutBuilderMountedHeroEditor($page))[0] ?? null;

    expect($field)->toBeInstanceOf(Field::class);
    assert($field instanceof Field);

    return $field;
}
