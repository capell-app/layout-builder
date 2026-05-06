<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Actions\HeroWidgetHasPrimaryHeadingAction;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;

it('returns true and sets frontend data when the first asset translation has a title', function (): void {
    $language = Language::factory()->create();
    $widget = Widget::factory()->create();
    $heroPage = Page::factory()->create();
    $heroPage->translations()->delete();

    Translation::factory()->create([
        'translatable_type' => $heroPage->getMorphClass(),
        'translatable_id' => $heroPage->id,
        'language_id' => $language->id,
        'title' => 'Primary Heading Title',
        'content' => null,
    ]);

    WidgetAsset::factory()->widget($widget)->asset($heroPage)->create();

    $page = Page::factory()->withTranslations()->create();

    $result = HeroWidgetHasPrimaryHeadingAction::run($widget, $page);

    expect($result)->toBeTrue();
});

it('returns true when the first asset translation content contains an h1 tag', function (): void {
    $language = Language::factory()->create();
    $widget = Widget::factory()->create();
    $heroPage = Page::factory()->create();
    $heroPage->translations()->delete();

    Translation::factory()->create([
        'translatable_type' => $heroPage->getMorphClass(),
        'translatable_id' => $heroPage->id,
        'language_id' => $language->id,
        'title' => null,
        'content' => '<h1 class="hero">Welcome</h1><p>More content</p>',
    ]);

    WidgetAsset::factory()->widget($widget)->asset($heroPage)->create();

    $page = Page::factory()->withTranslations()->create();

    $result = HeroWidgetHasPrimaryHeadingAction::run($widget, $page);

    expect($result)->toBeTrue();
});

it('falls back to page hero meta when the widget has no assets and content contains h1', function (): void {
    $language = Language::factory()->create();
    $widget = Widget::factory()->create();

    $page = Page::factory()->create();

    Translation::factory()->create([
        'translatable_type' => $page->getMorphClass(),
        'translatable_id' => $page->id,
        'language_id' => $language->id,
        'title' => 'Page Title',
        'meta' => ['hero' => '<h1>Hero Heading</h1>'],
    ]);

    $result = HeroWidgetHasPrimaryHeadingAction::run($widget, $page);

    expect($result)->toBeTrue();
});

it('returns false when the widget has no assets and page hero meta has no h1', function (): void {
    $language = Language::factory()->create();
    $widget = Widget::factory()->create();

    $page = Page::factory()->create();

    Translation::factory()->create([
        'translatable_type' => $page->getMorphClass(),
        'translatable_id' => $page->id,
        'language_id' => $language->id,
        'title' => 'Page Title',
        'meta' => ['hero' => '<p>No heading here</p>'],
    ]);

    $result = HeroWidgetHasPrimaryHeadingAction::run($widget, $page);

    expect($result)->toBeFalse();
});
