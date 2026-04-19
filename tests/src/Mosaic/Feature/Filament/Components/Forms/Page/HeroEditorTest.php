<?php

declare(strict_types=1);

use Capell\Core\Models\Translation;
use Capell\Mosaic\Database\Factories\LayoutFactory;
use Capell\Mosaic\Database\Factories\WidgetAssetFactory;
use Capell\Mosaic\Database\Factories\WidgetFactory;
use Capell\Mosaic\Filament\Components\Forms\Page\HeroEditor;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

uses(CreatesAdminUser::class);

test('hero editor sets correct state path', function (): void {
    $component = HeroEditor::make([]);

    expect($component->getStatePath())->toBe('meta');
});

test('hero editor is not visible when record is null', function (): void {
    $component = HeroEditor::make([]);

    expect($component->isVisible())->toBeFalse();
});

test('hero editor is visible for pageable records without hero assets', function (): void {
    $layout = LayoutFactory::new()->create();

    $component = HeroEditor::make([])
        ->model($layout);

    expect($component->isVisible())->toBeTrue();
});

test('hero editor is hidden for pageable records with hero widget assets', function (): void {
    $layout = LayoutFactory::new()->create();

    $heroWidget = WidgetFactory::new()
        ->state(['key' => 'hero-banner'])
        ->create();

    WidgetAssetFactory::new()
        ->state([
            'widget_id' => $heroWidget->id,
            'pageable_type' => $layout->getMorphClass(),
            'pageable_id' => $layout->id,
        ])
        ->create();

    $component = HeroEditor::make([])
        ->model($layout);

    expect($component->isVisible())->toBeFalse();
});

test('hero editor is visible for translation records without hero assets', function (): void {
    $layout = LayoutFactory::new()->create();
    $translation = Translation::factory()
        ->state(['translatable_type' => $layout::class, 'translatable_id' => $layout->id])
        ->create();

    $component = HeroEditor::make([])
        ->model($translation);

    expect($component->isVisible())->toBeTrue();
});

test('hero editor is hidden for translation records with hero widget assets', function (): void {
    $layout = LayoutFactory::new()->create();
    $translation = Translation::factory()
        ->state(['translatable_type' => $layout::class, 'translatable_id' => $layout->id])
        ->create();

    $heroWidget = WidgetFactory::new()
        ->state(['key' => 'hero-section'])
        ->create();

    WidgetAssetFactory::new()
        ->state([
            'widget_id' => $heroWidget->id,
            'pageable_type' => $layout->getMorphClass(),
            'pageable_id' => $layout->id,
        ])
        ->create();

    $component = HeroEditor::make([])
        ->model($translation);

    expect($component->isVisible())->toBeFalse();
});

test('hero editor caches hero asset existence checks', function (): void {
    $layout = LayoutFactory::new()->create();
    $cacheKey = sprintf('page-%d-has-hero-widget-assets', $layout->id);

    cache()->forget($cacheKey);

    $component = HeroEditor::make([])
        ->model($layout);

    $firstCheck = $component->isVisible();

    expect(cache()->has($cacheKey))->toBeTrue();

    cache()->forget($cacheKey);
    $secondCheck = $component->isVisible();

    expect($firstCheck)->toBe($secondCheck);
});
