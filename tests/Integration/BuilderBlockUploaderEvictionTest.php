<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\BlockLibrary\Actions\ResolveBuilderBlockMediaAction;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Frontend\Actions\GenerateStaticPageArtifactsAction;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

require_once dirname(__DIR__, 4) . '/tests/Packages/Support/ThemeLayoutNativeSupport.php';

uses(CreatesAdminUser::class);

it('evicts an actual static artifact when replacing block media through the real uploader', function (): void {
    Storage::fake('public');
    $artifactRoot = storage_path('framework/testing/builder-static-' . Str::uuid());
    config(['filesystems.disks.public.visibility' => 'public', 'capell-frontend.static_artifacts_path' => $artifactRoot]);
    [$pageUrl] = layoutNativeThemeCreatePage('default', 'Media eviction');
    $page = Page::query()->where('meta->theme_demo->surface', 'homepage')->firstOrFail();
    $page->update(['content_structure_override' => ContentStructure::Blocks->value]);

    $layout = $page->layout ?? throw new LogicException('Expected a layout.');
    $layout->update(['containers' => null]);

    $translation = $page->translations()->firstOrFail();
    $key = (string) Str::uuid();
    $image = $translation->addMedia(UploadedFile::fake()->image('evict.png'))->toMediaCollection(ResolveBuilderBlockMediaAction::collection($key) ?? throw new LogicException('Expected a collection.'), 'public');
    $translation->update(['content' => json_encode([['type' => 'logos', 'data' => ['title' => 'Eviction logos', 'media_key' => $key, 'logos' => [['name' => 'Partner', 'image' => $image->uuid]]]]], JSON_THROW_ON_ERROR)]);
    CapellLayoutManager::clearContainerWidgets();
    try {
        $manifest = GenerateStaticPageArtifactsAction::run($page->site_id, [$pageUrl->url]);
        expect($manifest['artifacts'])->toHaveCount(1);
        $file = data_get($manifest, 'artifacts.0.file');
        throw_unless(is_string($file), LogicException::class);
        $path = $artifactRoot . '/' . $file;
        expect(is_file($path))->toBeTrue()->and(file_get_contents($path))->toContain($image->getUrl());
        $this->actingAsAdmin();
        $editor = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->assertSuccessful();
        $instance = $editor->instance();
        throw_unless($instance instanceof EditPage, LogicException::class);
        $form = $instance->getSchema('form');
        throw_unless($form instanceof Schema, LogicException::class);
        $upload = collect($form->getFlatFields(withHidden: true))->first(static fn ($field): bool => $field instanceof SpatieMediaLibraryFileUpload && $field->getName() === 'block_images');
        $selector = collect($form->getFlatFields(withHidden: true))->first(static fn ($field): bool => $field instanceof Select && $field->getName() === 'image');
        throw_unless($upload instanceof SpatieMediaLibraryFileUpload && $selector instanceof Select, LogicException::class);
        $editor->set($selector->getStatePath());
        $editor->set($upload->getStatePath(), []);
        $replacementUpload = UploadedFile::fake()->image('uploader-replacement.png');
        $replacementContents = $replacementUpload->getContent();
        $editor->set($upload->getStatePath(), [$replacementUpload]);
        $editor->call('save')->assertHasNoFormErrors();
        $savedTranslation = $translation->fresh() ?? throw new LogicException('Missing saved translation.');
        $replacement = $savedTranslation->getMedia(ResolveBuilderBlockMediaAction::collection($key))->sole();
        expect($replacement->uuid)->not->toBe($image->uuid)
            ->and(Storage::disk('public')->get($replacement->getPathRelativeToRoot()))->toBe($replacementContents)
            ->and(Storage::disk('public')->exists($replacement->getPathRelativeToRoot()))->toBeTrue();
        expect(Media::query()->whereKey($image->id)->exists())->toBeFalse()
            ->and(is_file($path))->toBeFalse();
    } finally {
        File::deleteDirectory($artifactRoot);
    }
});
