<?php

declare(strict_types=1);

use Capell\Admin\Filament\Components\Forms\Editor\ContentBuilder;
use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\BlockLibrary\Actions\BuildBuilderBlockRenderDataAction;
use Capell\BlockLibrary\Actions\ResolveBuilderBlockMediaAction;
use Capell\BlockLibrary\View\Components\BuilderBlock;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\EventSourcing\Serializers\PageStateSerializer;
use Capell\Core\Models\Language;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

uses(CreatesAdminUser::class);

it('keeps separate block media collections and image selections when saving the real page editor', function (): void {
    $this->actingAsAdmin();
    Storage::fake('public');
    config(['filesystems.disks.public.visibility' => 'public']);
    $keys = [(string) Str::uuid(), (string) Str::uuid()];
    $page = Page::factory()->state(['content_structure_override' => ContentStructure::Blocks->value])->create();
    $translation = $page->translations()->create(['language_id' => Language::factory()->create()->id, 'title' => 'Page']);
    $images = [];
    foreach ($keys as $index => $key) {
        $images[] = $translation->addMedia(UploadedFile::fake()->image('logo-' . $index . '.png'))
            ->toMediaCollection(ResolveBuilderBlockMediaAction::collection($key), 'public');
    }

    $blocks = [];
    foreach ($keys as $index => $key) {
        $blocks[] = ['type' => 'logos', 'data' => ['title' => 'Partners', 'media_key' => $key, 'logos' => [['name' => 'Partner', 'image' => $images[$index]->uuid]]]];
    }

    $translation->update(['content' => $blocks]);
    $editor = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->assertSuccessful();
    $instance = $editor->instance();
    throw_unless($instance instanceof EditPage, RuntimeException::class);
    $form = $instance->getSchema('form');
    throw_unless($form instanceof Schema, RuntimeException::class);
    $uploads = [];
    $selectors = [];
    foreach ($form->getFlatFields(withHidden: true) as $field) {
        if ($field instanceof SpatieMediaLibraryFileUpload && $field->getName() === 'block_images') {
            $uploads[] = $field;
        }

        if ($field instanceof Select && $field->getName() === 'image') {
            $selectors[] = $field;
        }
    }

    expect($uploads)->toHaveCount(2)->and($selectors)->toHaveCount(2);
    foreach ($uploads as $index => $upload) {
        expect($upload->getCollection())->toBe(ResolveBuilderBlockMediaAction::collection($keys[$index]))
            ->and(array_keys($selectors[$index]->getOptions()))->toBe([$images[$index]->uuid]);
    }

    $editor->call('save')->assertHasNoFormErrors();
    $page->refresh();
    $translation->refresh();
    foreach ($keys as $index => $key) {
        expect($translation->getMedia(ResolveBuilderBlockMediaAction::collection($key))->pluck('uuid')->all())->toBe([$images[$index]->uuid]);
    }

    $saved = $page->translations()->firstOrFail()->content;
    expect($saved)->toBeArray();
    throw_unless(is_array($saved), RuntimeException::class);
    expect(array_column(array_column($saved, 'data'), 'media_key'))->toBe($keys);
});

it('clones then removes media through the real editor without deleting the original collection', function (): void {
    $this->actingAsAdmin();
    Storage::fake('public');
    config(['filesystems.disks.public.visibility' => 'public']);
    $key = (string) Str::uuid();
    $page = Page::factory()->create(['content_structure_override' => ContentStructure::Blocks->value]);
    $translation = $page->translations()->create(['language_id' => Language::factory()->create()->id, 'title' => 'Clone page']);
    $image = $translation->addMedia(UploadedFile::fake()->image('original.png'))->toMediaCollection(ResolveBuilderBlockMediaAction::collection($key), 'public');
    $translation->update(['content' => [['type' => 'logos', 'data' => ['title' => 'Partners', 'media_key' => $key, 'logos' => [['name' => 'Partner', 'image' => $image->uuid]]]]]]);
    $editor = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->assertSuccessful();
    $instance = $editor->instance();
    throw_unless($instance instanceof EditPage, LogicException::class);
    $form = $instance->getSchema('form');
    throw_unless($form instanceof Schema, LogicException::class);
    $builder = collect($form->getFlatFields(withHidden: true))->first(static fn ($field): bool => $field instanceof ContentBuilder);
    throw_unless($builder instanceof ContentBuilder, LogicException::class);
    $state = $builder->getRawState();
    throw_unless(is_array($state), LogicException::class);
    $builder->getCloneAction()->call(['arguments' => ['item' => array_key_first($state)], 'component' => $builder]);
    $state = $builder->getRawState();
    throw_unless(is_array($state), LogicException::class);
    $cloned = data_get(array_values($state), '1.data');
    throw_unless(is_array($cloned) && is_string($cloned['media_key'] ?? null), LogicException::class);
    expect($cloned['media_key'])->not->toBe($key);
    $cloneKey = $cloned['media_key'];
    $editor->set($builder->getStatePath(), $builder->getRawState());
    $cloneImage = $translation->fresh()->getMedia(ResolveBuilderBlockMediaAction::collection($cloneKey))->first();
    expect($cloneImage)->not->toBeNull()->and($cloneImage->uuid)->not->toBe($image->uuid)
        ->and(data_get($cloned, 'logos.*.image'))->toBe([$cloneImage->uuid]);
    expect($cloned['block_images'])->toBe([$cloneImage->uuid => $cloneImage->uuid]);
    $editor->call('save')->assertHasNoFormErrors();
    expect(Media::query()->whereKey($cloneImage->id)->exists())->toBeTrue()
        ->and(Storage::disk('public')->exists($cloneImage->getPathRelativeToRoot()))->toBeTrue();
    $editor = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->assertSuccessful();
    $instance = $editor->instance();
    throw_unless($instance instanceof EditPage, LogicException::class);
    $form = $instance->getSchema('form');
    throw_unless($form instanceof Schema, LogicException::class);
    $uploads = array_values(array_filter($form->getFlatFields(withHidden: true), static fn (Field $field): bool => $field instanceof SpatieMediaLibraryFileUpload && $field->getName() === 'block_images'));
    expect($uploads)->toHaveCount(2);
    $selectors = array_values(array_filter($form->getFlatFields(withHidden: true), static fn (Field $field): bool => $field instanceof Select && $field->getName() === 'image'));
    expect($uploads[1]->getRawState())->toBe([$cloneImage->uuid => $cloneImage->uuid])
        ->and($selectors[1]->getState())->toBe($cloneImage->uuid)
        ->and(Media::query()->whereKey($cloneImage->id)->exists())->toBeTrue()
        ->and(Storage::disk('public')->exists($cloneImage->getPathRelativeToRoot()))->toBeTrue();
    $editor->set($selectors[1]->getStatePath());
    $editor->set($uploads[1]->getStatePath(), []);
    $editor->call('save')->assertHasNoFormErrors();
    $translation->refresh();
    expect($translation->getMedia(ResolveBuilderBlockMediaAction::collection($key))->pluck('uuid')->all())->toBe([$image->uuid])
        ->and($translation->getMedia(ResolveBuilderBlockMediaAction::collection($cloneKey)))->toBeEmpty()
        ->and(Storage::disk('public')->exists($image->getPathRelativeToRoot()))->toBeTrue();

    $editor = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->assertSuccessful();
    $instance = $editor->instance();
    throw_unless($instance instanceof EditPage, LogicException::class);
    $form = $instance->getSchema('form');
    throw_unless($form instanceof Schema, LogicException::class);
    // Discard a whole populated clone before its first save. The saved content
    // is unchanged; conservative retention preserves the discarded media.
    $builder = collect($form->getFlatFields(withHidden: true))->first(static fn ($field): bool => $field instanceof ContentBuilder);
    throw_unless($builder instanceof ContentBuilder, LogicException::class);
    $state = $builder->getRawState();
    throw_unless(is_array($state), LogicException::class);
    $builder->getCloneAction()->call(['arguments' => ['item' => array_key_first($state)], 'component' => $builder]);
    $stateWithClone = $builder->getRawState();
    throw_unless(is_array($stateWithClone), LogicException::class);
    expect($translation->media()->where('collection_name', 'like', 'builder-block-%')->count())->toBe(2);
    $editor->set($builder->getStatePath(), $state);
    $editor->call('save')->assertHasNoFormErrors();
    expect($translation->media()->where('collection_name', 'like', 'builder-block-%')->count())->toBe(2);
});

it('retains committed block media and renders its original image after restoring saved page state', function (bool $restore): void {
    $this->actingAsAdmin();
    Storage::fake('public');
    config(['filesystems.disks.public.visibility' => 'public']);
    $page = Page::factory()->create(['content_structure_override' => ContentStructure::Blocks->value]);
    $translation = Translation::factory()->translatable($page)->create();
    $key = (string) Str::uuid();
    $image = $translation->addMedia(UploadedFile::fake()->image('committed.png'))
        ->toMediaCollection(ResolveBuilderBlockMediaAction::collection($key), 'public');
    $metadata = $image->translations()->create(['language_id' => $translation->language_id, 'meta' => ['alt' => 'Original partner']]);
    $translation->update(['content' => [['type' => 'logos', 'data' => ['media_key' => $key, 'logos' => [['name' => 'Partner', 'image' => $image->uuid]]]]]]);
    $editor = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->assertSuccessful();
    $editor->call('save')->assertHasNoFormErrors();
    $serializer = resolve(PageStateSerializer::class);
    $snapshot = $serializer->capture($page);
    $instance = $editor->instance();
    throw_unless($instance instanceof EditPage, LogicException::class);
    $form = $instance->getSchema('form');
    throw_unless($form instanceof Schema, LogicException::class);
    $builder = collect($form->getFlatFields(withHidden: true))->first(static fn ($field): bool => $field instanceof ContentBuilder);
    throw_unless($builder instanceof ContentBuilder, LogicException::class);
    $editor->set($builder->getStatePath(), []);
    $editor->call('save')->assertHasNoFormErrors();
    expect($translation->fresh()?->content)->toBeEmpty()
        ->and(Media::query()->whereKey($image->id)->exists())->toBeTrue()
        ->and(Translation::query()->whereKey($metadata->id)->exists())->toBeTrue()
        ->and(Storage::disk('public')->exists($image->getPathRelativeToRoot()))->toBeTrue();

    if ($restore) {
        $serializer->restore($page, $snapshot);
        $translation->refresh();
        $block = data_get($translation->content, '0');
        throw_unless(is_array($block) && is_array($block['data'] ?? null), LogicException::class);
        $data = BuildBuilderBlockRenderDataAction::run('logos', $block['data'], ResolveBuilderBlockMediaAction::run($translation, $block['data']['media_key']));
        $html = new BuilderBlock('logos', $data)->render()->render();
        $document = new DOMDocument;
        $document->loadHTML('<html><body>' . $html . '</body></html>', LIBXML_NOERROR | LIBXML_NOWARNING);
        expect($document->getElementsByTagName('img'))->toHaveCount(1)
            ->and($document->getElementsByTagName('img')->item(0)?->getAttribute('src'))->toBe($image->getUrl())
            ->and($document->getElementsByTagName('img')->item(0)?->getAttribute('alt'))->toBe('Original partner')
            ->and(Storage::disk('public')->exists($image->getPathRelativeToRoot()))->toBeTrue();
    }
})->with(['remove and save' => false, 'restore earlier page state' => true]);

it('preserves another editors unsaved clone media metadata and files during an unrelated save', function (): void {
    $this->actingAsAdmin();
    Storage::fake('public');
    config(['filesystems.disks.public.visibility' => 'public']);
    $page = Page::factory()->create(['content_structure_override' => ContentStructure::Blocks->value]);
    $translation = Translation::factory()->translatable($page)->create();
    $key = (string) Str::uuid();
    $image = $translation->addMedia(UploadedFile::fake()->image('shared.png'))
        ->toMediaCollection(ResolveBuilderBlockMediaAction::collection($key), 'public');
    $image->translations()->create(['language_id' => $translation->language_id, 'meta' => ['alt' => 'Shared partner']]);
    $translation->update(['content' => [['type' => 'logos', 'data' => ['title' => 'Partners', 'media_key' => $key, 'logos' => [['name' => 'Partner', 'image' => $image->uuid]]]]]]);
    $editorA = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->assertSuccessful();
    $editorB = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->assertSuccessful();
    $instance = $editorB->instance();
    throw_unless($instance instanceof EditPage, LogicException::class);
    $form = $instance->getSchema('form');
    throw_unless($form instanceof Schema, LogicException::class);
    $builderB = collect($form->getFlatFields(withHidden: true))->first(static fn ($field): bool => $field instanceof ContentBuilder);
    throw_unless($builderB instanceof ContentBuilder, LogicException::class);
    $state = $builderB->getRawState();
    throw_unless(is_array($state), LogicException::class);
    $builderB->getCloneAction()->call(['arguments' => ['item' => array_key_first($state)], 'component' => $builderB]);
    $cloneState = $builderB->getRawState();
    $cloneKey = data_get($cloneState, array_key_last(is_array($cloneState) ? $cloneState : []) . '.data.media_key');
    throw_unless(is_string($cloneKey), LogicException::class);
    $clone = $translation->fresh()?->getMedia(ResolveBuilderBlockMediaAction::collection($cloneKey))->first();
    throw_unless($clone instanceof Media, LogicException::class);
    $metadata = $clone->translations()->firstOrFail();
    $attributes = $metadata->getAttributes();
    $files = Storage::disk('public')->allFiles();
    $instance = $editorA->instance();
    throw_unless($instance instanceof EditPage, LogicException::class);
    $form = $instance->getSchema('form');
    throw_unless($form instanceof Schema, LogicException::class);
    $builderA = collect($form->getFlatFields(withHidden: true))->first(static fn ($field): bool => $field instanceof ContentBuilder);
    throw_unless($builderA instanceof ContentBuilder, LogicException::class);
    $stateA = $builderA->getRawState();
    throw_unless(is_array($stateA), LogicException::class);
    $firstKey = array_key_first($stateA);
    $blockA = $stateA[$firstKey] ?? null;
    throw_unless($firstKey !== null && is_array($blockA) && is_array($blockA['data'] ?? null), LogicException::class);
    $blockA['data']['title'] = 'Updated partners';
    $stateA[$firstKey] = $blockA;
    $editorA->set($builderA->getStatePath(), $stateA);
    $editorA->call('save')->assertHasNoFormErrors();

    expect(data_get($translation->fresh()?->content, '0.data.title'))->toBe('Updated partners')
        ->and(data_get($translation->fresh()?->content, '*.data.media_key'))->toBe([$key])
        ->and(Media::query()->whereKey($clone->id)->exists())->toBeTrue()
        ->and($metadata->fresh()?->getAttributes())->toBe($attributes)
        ->and(Storage::disk('public')->exists($clone->getPathRelativeToRoot()))->toBeTrue()
        ->and(Storage::disk('public')->allFiles())->toEqualCanonicalizing($files)
        ->and($builderB->getRawState())->toBe($cloneState);
});

it('compensates all copied media and files when the second copy fails before publishing clone state', function (string $failure): void {
    $this->actingAsAdmin();
    Storage::fake('public');
    config(['filesystems.disks.public.visibility' => 'public']);
    $page = Page::factory()->create(['content_structure_override' => ContentStructure::Blocks->value]);
    $owner = Translation::factory()->translatable($page)->create();
    $key = (string) Str::uuid();
    $collection = ResolveBuilderBlockMediaAction::collection($key);
    $images = [];
    foreach (['one.png', 'two.png'] as $name) {
        $images[] = $owner->addMedia(UploadedFile::fake()->image($name))->toMediaCollection($collection, 'public');
    }

    foreach ($images as $image) {
        $image->translations()->create(['language_id' => $owner->language_id, 'meta' => ['alt' => 'Partner']]);
    }

    $translationCount = Translation::withTrashed()->count();
    $mediaCount = Media::withTrashed()->count();
    $files = Storage::disk('public')->allFiles();
    $block = ['type' => 'logos', 'data' => ['media_key' => $key]];
    $owner->update(['content' => [$block]]);
    $editor = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->assertSuccessful();
    $instance = $editor->instance();
    throw_unless($instance instanceof EditPage, LogicException::class);
    $form = $instance->getSchema('form');
    throw_unless($form instanceof Schema, LogicException::class);
    $builder = collect($form->getFlatFields(withHidden: true))->first(static fn ($field): bool => $field instanceof ContentBuilder);
    throw_unless($builder instanceof ContentBuilder, LogicException::class);
    $state = $builder->getRawState();
    throw_unless(is_array($state), LogicException::class);
    $copies = 0;
    if ($failure === 'copy') {
        Media::creating(static function (Media $media) use ($collection, &$copies): void {
            if ($media->collection_name !== $collection && ++$copies === 2) {
                throw new RuntimeException('Injected second copy failure');
            }
        });
    } else {
        $originalIds = array_map(static fn (Media $image): int => $image->id, $images);
        Translation::creating(static function (Translation $translation) use ($originalIds, $images, &$copies): void {
            if ($translation->translatable_type === $images[0]->getMorphClass() && ! in_array($translation->translatable_id, $originalIds, true) && ++$copies === 2) {
                throw new RuntimeException('Injected second copy failure');
            }
        });
    }

    expect(function () use ($builder, $state): void {
        $builder->getCloneAction()->call(['arguments' => ['item' => array_key_first($state)], 'component' => $builder]);
    })->toThrow(RuntimeException::class, 'Injected second copy failure');
    $failedState = $builder->getRawState();
    throw_unless(is_array($failedState), LogicException::class);
    // The editor has already staged its clone before invoking state hooks; the
    // failed media operation must never publish a new collection or selectors.
    expect(data_get(array_values($failedState), '*.data.media_key'))->toBe([$key, $key])
        ->and(data_get(array_values($failedState), '*.data.block_images'))->toBe(array_fill(0, 2, data_get(array_values($state), '0.data.block_images')))
        ->and($copies)->toBe(2)->and($owner->media()->pluck('uuid')->all())->toBe(array_map(static fn (Media $image): string => $image->uuid ?? throw new LogicException('Missing media UUID.'), $images))
        ->and(Storage::disk('public')->allFiles())->toEqualCanonicalizing($files)
        ->and(Translation::withTrashed()->count())->toBe($translationCount)
        ->and(Media::withTrashed()->count())->toBe($mediaCount);
})->with(['copy', 'metadata']);

it('refuses an occupied target collection and never compensates media it did not create', function (): void {
    $this->actingAsAdmin();
    Storage::fake('public');
    config(['filesystems.disks.public.visibility' => 'public']);
    $page = Page::factory()->create(['content_structure_override' => ContentStructure::Blocks->value]);
    $owner = Translation::factory()->translatable($page)->create();
    $key = (string) Str::uuid();
    $image = $owner->addMedia(UploadedFile::fake()->image('original.png'))->toMediaCollection(ResolveBuilderBlockMediaAction::collection($key), 'public');
    $image->translations()->create(['language_id' => $owner->language_id, 'meta' => ['alt' => 'Original']]);
    $files = Storage::disk('public')->allFiles();
    $mediaCount = Media::withTrashed()->count();
    $translationCount = Translation::withTrashed()->count();
    $owner->update(['content' => [['type' => 'logos', 'data' => ['media_key' => $key]]]]);
    $editor = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->assertSuccessful();
    $instance = $editor->instance();
    throw_unless($instance instanceof EditPage, LogicException::class);
    $form = $instance->getSchema('form');
    throw_unless($form instanceof Schema, LogicException::class);
    $builder = collect($form->getFlatFields(withHidden: true))->first(static fn ($field): bool => $field instanceof ContentBuilder);
    throw_unless($builder instanceof ContentBuilder, LogicException::class);
    $state = $builder->getRawState();
    throw_unless(is_array($state), LogicException::class);

    // Force every generated key to collide with the original block's collection.
    Str::createUuidsUsing(static fn (): UuidInterface => Uuid::fromString($key));

    try {
        expect(function () use ($builder, $state): void {
            $builder->getCloneAction()->call(['arguments' => ['item' => array_key_first($state)], 'component' => $builder]);
        })->toThrow(LogicException::class, 'Could not allocate an unused builder block media collection.');
    } finally {
        Str::createUuidsNormally();
    }

    expect($owner->media()->pluck('uuid')->all())->toBe([$image->uuid])
        ->and(Storage::disk('public')->allFiles())->toEqualCanonicalizing($files)
        ->and(Media::withTrashed()->count())->toBe($mediaCount)
        ->and(Translation::withTrashed()->count())->toBe($translationCount);
});

it('preserves committed media files when an editor content save rolls back', function (): void {
    $this->actingAsAdmin();
    Storage::fake('public');
    $page = Page::factory()->create(['content_structure_override' => ContentStructure::Blocks->value]);
    $translation = Translation::factory()->translatable($page)->create();
    $key = (string) Str::uuid();
    $image = $translation->addMedia(UploadedFile::fake()->image('rollback.png'))->toMediaCollection(ResolveBuilderBlockMediaAction::collection($key), 'public');
    $translation->update(['content' => [['type' => 'logos', 'data' => ['media_key' => $key]]]]);
    $editor = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->assertSuccessful();
    $instance = $editor->instance();
    throw_unless($instance instanceof EditPage, LogicException::class);
    $form = $instance->getSchema('form');
    throw_unless($form instanceof Schema, LogicException::class);
    $builder = collect($form->getFlatFields(withHidden: true))->first(static fn ($field): bool => $field instanceof ContentBuilder);
    throw_unless($builder instanceof ContentBuilder, LogicException::class);
    $editor->set($builder->getStatePath(), []);
    expect(function () use ($editor): void {
        DB::transaction(static function () use ($editor): void {
            $editor->call('save')->assertHasNoFormErrors();
            throw new RuntimeException('Roll back content');
        });
    })->toThrow(RuntimeException::class, 'Roll back content');
    expect(Media::query()->whereKey($image->id)->exists())->toBeTrue()
        ->and(Storage::disk('public')->exists($image->getPathRelativeToRoot()))->toBeTrue();
});
