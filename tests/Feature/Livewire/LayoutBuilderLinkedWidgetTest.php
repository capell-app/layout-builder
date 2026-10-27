<?php

declare(strict_types=1);

use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Actions\MakeIndependentWidgetCopyAction;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Dom\HTMLDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('discloses linked repetition before changing a dedicated page layout without revealing other placements', function (): void {
    $widget = Widget::factory()->create();
    $layout = Layout::factory()->create(['containers' => ['main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]]]]);
    $page = Page::factory()->create(['layout_id' => $layout->getKey()]);
    $privateLayout = Layout::factory()->create(['containers' => ['secret' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]]]]);
    $privatePage = Page::factory()->create(['layout_id' => $privateLayout->getKey(), 'name' => 'Private placement must stay private']);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout, 'page' => $page])
        ->mountAction('duplicateWidget', ['containerKey' => 'main', 'widgetIndex' => 0])
        ->assertActionMounted('duplicateWidget')
        ->assertDispatched('sync-action-modals', newActionNestingIndex: 0)
        ->assertDontSee('Private placement must stay private');

    // Modal updates arrive as a partial; assertSee() only reads the main HTML.
    $modalHtml = data_get($component->effects, 'partials.action-modals');
    $this->assertIsString($modalHtml);
    $document = HTMLDocument::createFromString($modalHtml, LIBXML_NOERROR);
    $description = $document->querySelector('.fi-modal-description');
    $this->assertNotNull($description);
    expect(trim((string) $description->textContent))->toBe(__('capell-layout-builder::message.duplicate_widget_linked'))
        ->and($document->querySelector('.fi-modal')?->getAttribute('aria-describedby'))->toBe($description->id);
    $this->assertStringNotContainsString('Private placement must stay private', $modalHtml);

    expect(data_get($component->get('containers'), 'main.widgets'))->toHaveCount(1);
    $component->callMountedAction();
    expect(data_get($component->get('containers'), 'main.widgets'))->toHaveCount(2);
    expect(data_get($component->get('containers'), 'main.widgets.1.widget_key'))->toBe($widget->key);
    expect($privatePage->refresh()->layout_id)->toBe($privateLayout->getKey());
});

it('keeps linked content shared and makes one occurrence independently editable with its own media', function (): void {
    Storage::fake('public');
    $widget = Widget::factory()->create(['meta' => ['body' => 'Original']]);
    Translation::factory()->translatable($widget)->create(['title' => 'Original title']);
    $widget->addMedia(UploadedFile::fake()->image('original.jpg'))->toMediaCollection(MediaCollectionEnum::Image->value, 'public');
    $layout = Layout::factory()->create(['containers' => ['main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]]]]);
    $page = Page::factory()->create(['layout_id' => $layout->getKey()]);
    $foreignPage = Page::factory()->create();
    WidgetAsset::factory()->widget($widget)->page($foreignPage, 'private', 9)->create();

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout, 'page' => $page]);
    $instance = $component->instance();
    $this->assertInstanceOf(LayoutBuilder::class, $instance);
    $instance->duplicateWidget('main', 0);
    $widget->update(['meta' => ['body' => 'Linked edit']]);
    expect($instance->getContainerWidget('main', 0)->refresh()->meta)->toBe(['body' => 'Linked edit'])
        ->and($instance->getContainerWidget('main', 1)->refresh()->meta)->toBe(['body' => 'Linked edit']);

    $instance->makeIndependentWidgetCopy('main', 1);
    $copy = $instance->getContainerWidget('main', 1);
    expect($copy->getKey())->not->toBe($widget->getKey())
        ->and($copy->key)->not->toBe($widget->key)
        ->and($copy->translations()->count())->toBe($widget->translations()->count())
        ->and($copy->assets()->count())->toBe(0)
        ->and($copy->getFirstMedia(MediaCollectionEnum::Image->value)?->getKey())->not->toBe($widget->getFirstMedia(MediaCollectionEnum::Image->value)?->getKey());
    $copy->update(['meta' => ['body' => 'Independent edit']]);
    $copy->clearMediaCollection(MediaCollectionEnum::Image->value);
    $copy->addMedia(UploadedFile::fake()->image('independent.jpg'))->toMediaCollection(MediaCollectionEnum::Image->value, 'public');
    $translation = $copy->translations()->firstOrFail();
    $translation->update(['title' => 'Independent title']);
    expect($widget->refresh()->meta)->toBe(['body' => 'Linked edit'])
        ->and($widget->getFirstMedia(MediaCollectionEnum::Image->value)?->file_name)->toBe('original.jpg')
        ->and($widget->translations()->where('title', 'Independent title')->exists())->toBeFalse();

    expect($instance->saveLayout())->toBeTrue();
    $reopened = Livewire::test(LayoutBuilder::class, ['layout' => $layout->refresh(), 'page' => $page])->instance();
    $this->assertInstanceOf(LayoutBuilder::class, $reopened);
    expect($reopened->getContainerWidget('main', 0)->getKey())->toBe($widget->getKey())
        ->and($reopened->getContainerWidget('main', 1)->getKey())->toBe($copy->getKey())
        ->and($reopened->getContainerWidget('main', 1)->meta)->toBe(['body' => 'Independent edit']);
});

it('copies translation media references and selected placement uploads without importing foreign placements', function (): void {
    Storage::fake('public');
    $widget = Widget::factory()->create();
    $translation = Translation::factory()->translatable($widget)->create(['title' => 'Original title']);
    $media = $translation->addMedia(UploadedFile::fake()->image('block.jpg'))->toMediaCollection('block_images', 'public');
    $translation->update(['meta' => ['image' => $media->uuid]]);
    Translation::factory()->translatable($media)->create(['meta' => ['alt' => 'Original alternative text']]);
    $selected = WidgetAsset::factory()->widget($widget)->create(['occurrence' => 2]);
    $selectedMedia = $selected->addMedia(UploadedFile::fake()->image('placement.jpg'))->toMediaCollection(MediaCollectionEnum::Image->value, 'public');
    $foreign = WidgetAsset::factory()->widget($widget)->page(Page::factory()->create(), 'private', 9)->create();

    $copy = MakeIndependentWidgetCopyAction::run($widget, WidgetAsset::query()->whereKey($selected->getKey())->get());
    $copiedTranslation = $copy->translations()->firstOrFail();
    $copiedMedia = $copiedTranslation->getFirstMedia('block_images');
    $this->assertNotNull($copiedMedia);
    expect($copiedMedia->uuid)->not->toBe($media->uuid)
        ->and(data_get($copiedTranslation->meta, 'image'))->toBe($copiedMedia->uuid)
        ->and(data_get($translation->refresh()->meta, 'image'))->toBe($media->uuid)
        ->and($copy->assets)->toHaveCount(1);
    $mediaTranslation = Translation::query()->where('translatable_type', $copiedMedia->getMorphClass())->where('translatable_id', $copiedMedia->getKey())->firstOrFail();
    expect(data_get($mediaTranslation->meta, 'alt'))->toBe('Original alternative text');
    $copiedAsset = $copy->assets->firstOrFail();
    expect($copiedAsset->getKey())->not->toBe($selected->getKey())
        ->and($copiedAsset->occurrence)->toBe(1)
        ->and($copiedAsset->getFirstMedia(MediaCollectionEnum::Image->value)?->uuid)->not->toBe($selectedMedia->uuid)
        ->and($copy->assets()->where('asset_id', $foreign->asset_id)->exists())->toBeFalse();
    $copiedMedia->delete();
    expect($translation->refresh()->getFirstMedia('block_images')?->uuid)->toBe($media->uuid);
});
