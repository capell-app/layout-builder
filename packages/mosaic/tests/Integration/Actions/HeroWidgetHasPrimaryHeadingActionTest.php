<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\Mosaic\Actions\HeroWidgetHasPrimaryHeadingAction;
use Capell\Mosaic\Models\Section;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Models\WidgetAsset;

it('returns true and sets frontend data when the first asset translation has a title', function (): void {
    $language = Language::factory()->create();
    $widget = Widget::factory()->create();
    $section = Section::factory()->create();

    Translation::factory()->create([
        'translatable_type' => $section->getMorphClass(),
        'translatable_id' => $section->id,
        'language_id' => $language->id,
        'title' => 'Primary Heading Title',
        'content' => null,
    ]);

    WidgetAsset::factory()->widget($widget)->asset($section)->create();

    $page = Page::factory()->withTranslations()->create();

    $result = HeroWidgetHasPrimaryHeadingAction::run($widget, $page);

    expect($result)->toBeTrue();
});

it('returns true when the first asset translation content contains an h1 tag', function (): void {
    $language = Language::factory()->create();
    $widget = Widget::factory()->create();
    $section = Section::factory()->create();

    Translation::factory()->create([
        'translatable_type' => $section->getMorphClass(),
        'translatable_id' => $section->id,
        'language_id' => $language->id,
        'title' => null,
        'content' => '<h1 class="hero">Welcome</h1><p>More content</p>',
    ]);

    WidgetAsset::factory()->widget($widget)->asset($section)->create();

    $page = Page::factory()->withTranslations()->create();

    $result = HeroWidgetHasPrimaryHeadingAction::run($widget, $page);

    expect($result)->toBeTrue();
});

it('returns false when the first asset translation has no title and no h1 in content', function (): void {
    $language = Language::factory()->create();
    $widget = Widget::factory()->create();
    $section = Section::factory()->create();

    Translation::factory()->create([
        'translatable_type' => $section->getMorphClass(),
        'translatable_id' => $section->id,
        'language_id' => $language->id,
        'title' => null,
        'content' => '<p>Just a paragraph, no heading.</p>',
    ]);

    WidgetAsset::factory()->widget($widget)->asset($section)->create();

    $page = Page::factory()->withTranslations()->create();

    $result = HeroWidgetHasPrimaryHeadingAction::run($widget, $page);

    expect($result)->toBeFalse();
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
