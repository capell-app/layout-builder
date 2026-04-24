<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Mosaic\Database\Factories\WidgetAssetFactory;
use Capell\Mosaic\Database\Factories\WidgetFactory;
use Capell\Mosaic\Tests\Fixtures\Forms\HeroEditorTestFixture;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('hero-editor');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('hero editor is visible when page has no hero widget assets', function (): void {
    $page = Page::factory()->create();

    livewire(HeroEditorTestFixture::class, ['record' => $page])
        ->assertSuccessful()
        ->assertSchemaComponentVisible('meta');
});

test('hero editor is hidden when page has hero widget assets', function (): void {
    $page = Page::factory()->create();

    $heroWidget = WidgetFactory::new()
        ->state(['key' => 'hero-banner'])
        ->create();

    WidgetAssetFactory::new()
        ->state([
            'widget_id' => $heroWidget->id,
            'pageable_type' => $page->getMorphClass(),
            'pageable_id' => $page->id,
        ])
        ->create();

    livewire(HeroEditorTestFixture::class, ['record' => $page])
        ->assertSuccessful()
        ->assertSchemaComponentHidden('meta');
});

test('hero editor visibility is scoped per page', function (): void {
    $pageWithHero = Page::factory()->create();
    $pageWithoutHero = Page::factory()->create();

    $heroWidget = WidgetFactory::new()
        ->state(['key' => 'hero-banner'])
        ->create();

    WidgetAssetFactory::new()
        ->state([
            'widget_id' => $heroWidget->id,
            'pageable_type' => $pageWithHero->getMorphClass(),
            'pageable_id' => $pageWithHero->id,
        ])
        ->create();

    livewire(HeroEditorTestFixture::class, ['record' => $pageWithHero])
        ->assertSchemaComponentHidden('meta');

    livewire(HeroEditorTestFixture::class, ['record' => $pageWithoutHero])
        ->assertSchemaComponentVisible('meta');
});
