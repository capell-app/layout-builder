<?php

declare(strict_types=1);

use Capell\Admin\Contracts\Widgets\FilamentWidget;
use Capell\Admin\Filament\Components\Forms\Editor\ContentBuilder;
use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Admin\Support\Widgets\WidgetDiscovery;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderStatefulSchemaHarness;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\WidgetAudioPlaylist\Filament\AudioPlaylistWidget;
use Capell\WidgetBeforeAfter\Filament\BeforeAfterWidget;
use Capell\WidgetHotspots\Filament\HotspotsWidget;
use Capell\WidgetSlideshow\Filament\SlideshowWidget;
use Capell\WidgetYouTube\Filament\YouTubeWidget;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Illuminate\Filesystem\LocalFilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ClosureValidationRule;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Workbench\App\Support\WidgetScreenshotFixtures;

uses(CreatesAdminUser::class);

/** @return array{class-string<FilamentWidget>, int} */
function widgetUploadDefinition(string $widget): array
{
    return match ($widget) {
        'audio-playlist' => [AudioPlaylistWidget::class, 2],
        'before-after' => [BeforeAfterWidget::class, 2],
        'hotspots' => [HotspotsWidget::class, 1],
        'slideshow' => [SlideshowWidget::class, 2],
        'youtube' => [YouTubeWidget::class, 1],
        default => throw new InvalidArgumentException('Unknown upload widget.'),
    };
}

/** @return array<string, FileUpload> */
function widgetUploadFields(Schema $schema): array
{
    return array_filter($schema->getFlatFields(withHidden: true), fn (Field $field): bool => $field instanceof FileUpload && str_contains(widgetUploadStatePath($field), '.content.'));
}

function widgetUploadStatePath(FileUpload $upload): string
{
    $path = $upload->getStatePath();
    if (! is_string($path) || $path === '') {
        throw new RuntimeException('Expected a bound widget upload field.');
    }

    return $path;
}

function widgetUploadPageForm(Testable $livewire): Schema
{
    $editor = $livewire->instance();
    if (! $editor instanceof EditPage) {
        throw new RuntimeException('Expected the page editor.');
    }

    $form = $editor->getSchema('form');
    if (! $form instanceof Schema) {
        throw new RuntimeException('Expected the page editor form.');
    }

    return $form;
}

function widgetUploadTestDisk(): LocalFilesystemAdapter
{
    // Separate package test runs reuse parallel tokens in the Testbench storage
    // directory, so another public fake must not delete these upload fixtures.
    return Storage::fake('widget-upload-state-' . bin2hex(random_bytes(8)));
}

beforeEach(function (): void {
    Storage::set('public', widgetUploadTestDisk());
    config()->set('filament.default_filesystem_disk', 'public');
    Storage::disk('public')->put('widget-screenshots/workspace.jpg', UploadedFile::fake()->image('workspace.jpg')->getContent());
    Storage::disk('public')->put('widget-screenshots/workflow.jpg', UploadedFile::fake()->image('workflow.jpg')->getContent());
    Storage::disk('public')->put('widget-screenshots/reference.wav', 'reference audio');
});

afterEach(function (): void {
    Storage::disk('public')->deleteDirectory('');
});

it('keeps upload fixtures when another test fakes the public disk', function (): void {
    $originalDisk = Storage::disk('public');
    $originalStoragePath = app()->storagePath();
    $storagePath = sys_get_temp_dir() . '/widget-upload-collision-' . bin2hex(random_bytes(8));

    try {
        app()->useStoragePath($storagePath);
        $uploadDisk = widgetUploadTestDisk();
        $uploadDisk->put('poster.jpg', 'stored poster');
        Storage::fake('public');

        $uploadDisk->assertExists('poster.jpg');
    } finally {
        app()->useStoragePath($originalStoragePath);
        Storage::set('public', $originalDisk);
        File::deleteDirectory($storagePath);
    }
});

it('normalises stored paths supplied on the lazy page editor upload request', function (string $widget): void {
    test()->actingAsAdmin();
    [$widgetClass, $expectedUploads] = widgetUploadDefinition($widget);
    resolve(WidgetDiscovery::class)->registerAuthoritative($widgetClass);
    $page = Page::factory()->state(['content_structure_override' => ContentStructure::Blocks->value])
        ->withTranslations(data: ['content' => json_encode(WidgetScreenshotFixtures::blocks($widget), JSON_THROW_ON_ERROR)], contentStructure: ContentStructure::Blocks)
        ->create();
    $livewire = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->assertSuccessful();
    $livewire->call('$refresh')->assertSuccessful();
    $form = widgetUploadPageForm($livewire);
    $uploads = widgetUploadFields($form);
    expect($uploads)->toHaveCount($expectedUploads);

    foreach ($uploads as $upload) {
        $path = $upload->getState();
        expect($path)->toBeString()
            ->and($upload->getUploadedFiles())->toHaveCount(1);

        // Replay the persisted single-file value on a subsequent request:
        // Livewire property updates do not run the schema's hydration casts.
        $statePath = widgetUploadStatePath($upload);
        $livewire->set($statePath, $path)
            ->call('callSchemaComponentMethod', $upload->getKey(), 'getUploadedFiles')
            ->assertSuccessful();
        $rawState = data_get($livewire->get('data'), substr($statePath, 5));
        expect($rawState)->toBeArray()->toHaveCount(1);
        if (! is_array($rawState)) {
            throw new RuntimeException('Expected array state after the lazy upload request.');
        }

        expect(array_values($rawState))->toBe([$path]);
    }
})->with(['audio-playlist', 'before-after', 'hotspots', 'slideshow', 'youtube']);

it('saves stored upload paths updated in the same Livewire request', function (string $widget): void {
    test()->actingAsAdmin();
    [$widgetClass, $expectedUploads] = widgetUploadDefinition($widget);
    resolve(WidgetDiscovery::class)->registerAuthoritative($widgetClass);
    $page = Page::factory()->state(['content_structure_override' => ContentStructure::Blocks->value])
        ->withTranslations(data: ['content' => json_encode(WidgetScreenshotFixtures::blocks($widget), JSON_THROW_ON_ERROR)], contentStructure: ContentStructure::Blocks)
        ->create();
    $livewire = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->assertSuccessful();
    $uploads = widgetUploadFields(widgetUploadPageForm($livewire));
    expect($uploads)->toHaveCount($expectedUploads);

    $updates = [];

    foreach ($uploads as $upload) {
        $path = $upload->getState();
        expect($path)->toBeString();
        $updates[widgetUploadStatePath($upload)] = $path;

        if ($widget === 'youtube') {
            $updates[str_replace('.poster', '.videoId', widgetUploadStatePath($upload))] = 'https://youtu.be/aqz-KE-bpKQ';
        }
    }

    // Both updates and save must share one request: a separate set() renders
    // the form and can normalise the upload state before validation runs.
    $livewire->update([['method' => 'save', 'params' => [], 'path' => '']], $updates)
        ->assertSuccessful()
        ->assertHasNoFormErrors();

    $content = $page->translations()->sole()->content;
    expect($content)->toBeArray()->toHaveCount(1);
    $savedData = data_get($content, '0.data');
    if (! is_array($savedData)) {
        throw new RuntimeException('Expected persisted widget data.');
    }

    $savedMedia = array_filter(Arr::dot($savedData), fn (string $key): bool => in_array(last(explode('.', $key)), ['audio', 'artwork', 'beforeImage', 'afterImage', 'image', 'poster'], true), ARRAY_FILTER_USE_KEY);
    $expectedMedia = array_filter(Arr::dot(WidgetScreenshotFixtures::states()[$widget]), fn (string $key): bool => in_array(last(explode('.', $key)), ['audio', 'artwork', 'beforeImage', 'afterImage', 'image', 'poster'], true), ARRAY_FILTER_USE_KEY);
    expect($savedMedia)->toHaveCount($expectedUploads)->toBe($expectedMedia);
})->with(['audio-playlist', 'before-after', 'hotspots', 'slideshow', 'youtube']);

it('round trips stored single files without changing their persisted shape or upload keys', function (string $widget): void {
    [$widgetClass, $expectedUploads] = widgetUploadDefinition($widget);
    resolve(WidgetDiscovery::class)->registerAuthoritative($widgetClass);
    $builder = ContentBuilder::make('translations.en.content');
    $schema = Schema::make(new LayoutBuilderStatefulSchemaHarness)
        ->statePath('data')
        ->components([$builder]);
    $schema->fill(['translations' => ['en' => ['content' => WidgetScreenshotFixtures::blocks($widget)]]]);

    $uploads = widgetUploadFields($schema);
    expect($uploads)->toHaveCount($expectedUploads);

    foreach ($uploads as $upload) {
        $original = $upload->getRawState();
        $path = $upload->getState();
        expect($original)->toBeArray()->toHaveCount(1)
            ->and($upload->getUploadedFiles())->toHaveCount(1)
            ->and($upload->getRawState())->toBe($original);
        $upload->saveUploadedFiles();
        expect($upload->getState())->toBe($path);
    }

    if ($widget === 'youtube') {
        $item = array_values($builder->getItems())[0];
        $videoId = $item->getComponent('videoId', withHidden: true);
        assert($videoId instanceof Field);
        $videoId->state('https://youtu.be/aqz-KE-bpKQ');
    }

    $state = $schema->getState(shouldCallHooksBefore: false);
    $blocks = data_get($state, 'translations.en.content');
    expect($blocks)->toBeArray()->toHaveCount(1);
    if (! is_array($blocks) || ! is_array($blocks[0] ?? null) || ! is_array($blocks[0]['data'] ?? null)) {
        throw new RuntimeException('Expected saved widget data.');
    }

    $media = fn (array $data): array => array_filter(Arr::dot($data), fn (string $key): bool => in_array(last(explode('.', $key)), ['audio', 'artwork', 'beforeImage', 'afterImage', 'image', 'poster'], true), ARRAY_FILTER_USE_KEY);
    // This also checks that nested repeaters save sequential lists.
    expect($media($blocks[0]['data']))->toBe($media(WidgetScreenshotFixtures::states()[$widget]));
})->with(['audio-playlist', 'before-after', 'hotspots', 'slideshow', 'youtube']);

it('stores a newly uploaded file as a single path and reopens it safely', function (): void {
    test()->actingAsAdmin();
    resolve(WidgetDiscovery::class)->registerAuthoritative(YouTubeWidget::class);
    $page = Page::factory()->state(['content_structure_override' => ContentStructure::Blocks->value])
        ->withTranslations(data: ['content' => json_encode(WidgetScreenshotFixtures::blocks('youtube'), JSON_THROW_ON_ERROR)], contentStructure: ContentStructure::Blocks)
        ->create();
    $livewire = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()]);
    $form = widgetUploadPageForm($livewire);
    $upload = array_values(widgetUploadFields($form))[0];
    $statePath = widgetUploadStatePath($upload);
    $livewire->set($statePath, [])
        ->set($statePath, [UploadedFile::fake()->image('new-poster.jpg')]);
    // The URL field's input contract differs from the stored video ID.
    $livewire->set(str_replace('.poster', '.videoId', $statePath), 'https://youtu.be/aqz-KE-bpKQ')
        ->call('save')->assertHasNoFormErrors();
    $translation = $page->translations()->sole();
    $content = $translation->content;
    $path = data_get($content, '0.data.poster');
    expect($path)->toBeString()->not->toBe('widget-screenshots/workspace.jpg');
    if (! is_string($path)) {
        throw new RuntimeException('Expected a persisted single-file path.');
    }

    Storage::disk('public')->assertExists($path);
    $reopened = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->assertSuccessful();
    $form = widgetUploadPageForm($reopened);
    $savedUpload = array_values(widgetUploadFields($form))[0];
    expect($savedUpload->getState())->toBe($path);
    $reopened->call('callSchemaComponentMethod', $savedUpload->getKey(), 'getUploadedFiles')->assertSuccessful();
});

it('keeps empty optional uploads safe on subsequent reads', function (mixed $state): void {
    resolve(WidgetDiscovery::class)->registerAuthoritative(AudioPlaylistWidget::class);
    $builder = ContentBuilder::make('translations.en.content');
    $schema = Schema::make(new LayoutBuilderStatefulSchemaHarness)->statePath('data')->components([$builder]);
    $schema->fill(['translations' => ['en' => ['content' => WidgetScreenshotFixtures::blocks('audio-playlist')]]]);

    $artwork = collect(widgetUploadFields($schema))->first(fn (FileUpload $upload): bool => $upload->getName() === 'artwork');
    assert($artwork instanceof FileUpload);
    $artwork->rawState($state);
    expect($artwork->getUploadedFiles())->toBe([]);
    $artwork->saveUploadedFiles();
    expect($artwork->getState())->toBeNull();
})->with(['null' => [null], 'empty string' => [''], 'empty array' => [[]]]);

it('retains file path authorisation when a lazy request supplies a string', function (): void {
    resolve(WidgetDiscovery::class)->registerAuthoritative(YouTubeWidget::class);
    $builder = ContentBuilder::make('translations.en.content');
    $schema = Schema::make(new LayoutBuilderStatefulSchemaHarness)->statePath('data')->components([$builder]);
    $schema->fill(['translations' => ['en' => ['content' => WidgetScreenshotFixtures::blocks('youtube')]]]);

    $upload = array_values(widgetUploadFields($schema))[0];
    $upload->preventFilePathTampering();
    Storage::disk('public')->put('another-record.jpg', 'private file');
    $upload->rawState('another-record.jpg');
    expect(array_values($upload->getUploadedFiles() ?? []))->toBe([null]);
});

it('retains required and file path tampering validation for raw string state', function (string $path, string $rule): void {
    resolve(WidgetDiscovery::class)->registerAuthoritative(YouTubeWidget::class);
    $builder = ContentBuilder::make('translations.en.content');
    $schema = Schema::make(new LayoutBuilderStatefulSchemaHarness)->statePath('data')->components([$builder]);
    $schema->fill(['translations' => ['en' => ['content' => WidgetScreenshotFixtures::blocks('youtube')]]]);

    $upload = array_values(widgetUploadFields($schema))[0];
    $upload->preventFilePathTampering();
    Storage::disk('public')->put('another-record.jpg', 'private file');
    $upload->rawState($path);

    try {
        // Validate directly without a render or upload read normalising state.
        $schema->validate();
        test()->fail('Expected the upload validation rule to reject the path.');
    } catch (ValidationException $validationException) {
        $errors = $validationException->errors();
        expect($errors)->toHaveKey(widgetUploadStatePath($upload));
        expect($validationException->validator->failed()[widgetUploadStatePath($upload)])->toHaveKey($rule);
    }
})->with(['empty required path' => ['', 'Required'], 'unauthorised path' => ['another-record.jpg', ClosureValidationRule::class]]);
