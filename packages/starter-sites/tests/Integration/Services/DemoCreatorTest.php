<?php

declare(strict_types=1);

use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Enums\MediaConversionEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\StarterSites\Support\Creator\DemoCreator;
use Capell\StarterSites\Support\Creator\DemoResourceResolver;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\assertDatabaseHas;

it('creates a demo site with languages, pages, and media', function (): void {
    useTinyDemoResources();
    Queue::fake();
    Storage::fake('public');

    config()->set('media-library.disk_name', 'public');
    config()->set('media-library.conversions_disk', 'public');

    $demoCreator = new DemoCreator;

    $language = Language::factory()->default()->create();

    $site = Site::factory()->language($language)->default()->withTranslations($language)->create();
    $demoCreator->setupSite($site);
    $pageData = [
        'name' => ['en' => 'Home', 'fr' => 'Accueil'],
        'title' => ['en' => 'Welcome', 'fr' => 'Bienvenue'],
    ];
    $page = $demoCreator->createPage($pageData, $site);

    $page->refresh();

    expect($page)->toBeInstanceOf(Page::class)
        ->and($page->translations)->not()->toBeEmpty()
        ->and($page->getMedia(MediaCollectionEnum::Image->value))->toHaveCount(1);

    assertDatabaseHas('sites', ['name' => $site->name]);
    assertDatabaseHas('languages', ['code' => 'en']);
    assertDatabaseHas('pages', ['name' => 'Home']);

    expect($page->translations->where('language_id', $language->id)->count())->toBeGreaterThan(0);
});

it('creates a child page and attaches video media', function (): void {
    Storage::fake('public');

    config()->set('media-library.disk_name', 'public');
    config()->set('media-library.conversions_disk', 'public');

    $demoCreator = new DemoCreator;

    $language = Language::factory()->default()->create();

    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $demoCreator->setupSite($site);

    $parentData = [
        'name' => ['en' => 'Parent', 'fr' => 'Parent'],
        'title' => ['en' => 'Parent Page', 'fr' => 'Page Parent'],
    ];
    $parentPage = $demoCreator->createPage($parentData, $site, createMedia: false);
    assert($parentPage instanceof Page);

    $childData = [
        'name' => ['en' => 'Child', 'fr' => 'Enfant'],
        'title' => ['en' => 'Child Page', 'fr' => 'Page Enfant'],
    ];
    $childPage = $demoCreator->createPage($childData, $site, parent: $parentPage, createMedia: false);
    assert($childPage instanceof Page);

    $demoCreator->createMedia($childPage, 'SampleVideo_1280x720_1mb', 'video');

    $videoMedia = $childPage->refresh()->getFirstMedia(MediaCollectionEnum::Video->value);
    assert($videoMedia !== null);

    expect($childPage->parent_id)->toBe($parentPage->id)
        ->and($childPage->getMedia(MediaCollectionEnum::Image->value))->toHaveCount(0)
        ->and($childPage->getMedia(MediaCollectionEnum::Video->value))->toHaveCount(1)
        ->and($videoMedia->collection_name)->toBe(MediaCollectionEnum::Video->value)
        ->and($videoMedia->file_name)->toEndWith('.mp4');
});

it('throws if demo image path is empty', function (): void {
    File::spy();
    $demoCreator = new DemoCreator;
    expect(fn (): string => $demoCreator->getRandomDemoImage('/nonexistent/path'))
        ->toThrow(UnexpectedValueException::class);
});

it('sets up site translations and domains for languages', function (): void {
    $demoCreator = new DemoCreator(url: 'https://example.com');
    $demoCreator->createDefaultLanguages();

    $languages = Language::all();

    $site = Site::factory()->default()->create(['name' => 'Demo']);
    $demoCreator->setupSite($site, $languages);

    foreach ($languages as $language) {
        $translation = $site->translations()->where('language_id', $language->id)->first();
        expect($translation)->not()->toBeNull();
    }

    expect($site->siteDomains()->count())->toBeGreaterThan(0);
});

it('throws when demo resource path does not exist', function (): void {
    expect(fn (): string => DemoCreator::getDemoResourcePath('missing-folder'))
        ->toThrow(Exception::class);
});

it('rejects demo zip entries that escape the extraction directory', function (): void {
    $capellDir = storage_path('app/capell');
    $demoDir = $capellDir . '/demo';
    $outsidePath = $capellDir . '/outside.txt';
    $zipPath = tempnam(sys_get_temp_dir(), 'capell-starter-sites-') . '.zip';

    File::deleteDirectory($demoDir);
    File::delete($outsidePath);

    $archive = new ZipArchive;
    $archive->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $archive->addFromString('../outside.txt', 'escaped');
    $archive->close();

    config()->set('capell-starter-sites.archive.checksum', hash_file('sha256', $zipPath));

    Http::fake([
        'https://capell.app/demo.zip' => Http::response(File::get($zipPath), 200),
    ]);

    $method = new ReflectionMethod(DemoCreator::class, 'ensureStorageDemoResources');

    expect(fn (): mixed => $method->invoke(null))
        ->toThrow(Exception::class, 'Unsafe demo zip entry');

    expect(File::exists($outsidePath))->toBeFalse();

    File::delete($zipPath);
});

it('rejects demo zip downloads with a checksum mismatch', function (): void {
    $capellDir = storage_path('app/capell');
    $demoDir = $capellDir . '/demo';
    $zipPath = tempnam(sys_get_temp_dir(), 'capell-starter-sites-') . '.zip';

    File::deleteDirectory($demoDir);

    $archive = new ZipArchive;
    $archive->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $archive->addFromString('demo/file.txt', 'demo');
    $archive->close();

    config()->set('capell-starter-sites.archive.checksum', str_repeat('0', 64));

    Http::fake([
        'https://capell.app/demo.zip' => Http::response(File::get($zipPath), 200),
    ]);

    $method = new ReflectionMethod(DemoCreator::class, 'ensureStorageDemoResources');

    expect(fn (): mixed => $method->invoke(null))
        ->toThrow(Exception::class, 'checksum mismatch');

    File::delete($zipPath);
});

it('rejects demo zip downloads larger than the configured maximum size', function (): void {
    $capellDir = storage_path('app/capell');
    $demoDir = $capellDir . '/demo';

    File::deleteDirectory($demoDir);
    $existingArchives = glob($capellDir . '/demo.*.zip');
    $existingStagingDirectories = glob($capellDir . '/demo_extract_*');
    collect($existingArchives === false ? [] : $existingArchives)->each(fn (string $path): bool => File::delete($path));
    collect($existingStagingDirectories === false ? [] : $existingStagingDirectories)->each(fn (string $path): bool => File::deleteDirectory($path));

    config()->set('capell-starter-sites.archive.max_bytes', 5);

    Http::fake([
        'https://capell.app/demo.zip' => Http::response(str_repeat('x', 6), 200),
    ]);

    $method = new ReflectionMethod(DemoCreator::class, 'ensureStorageDemoResources');

    expect(fn (): mixed => $method->invoke(null))
        ->toThrow(Exception::class, 'maximum size');

    $leftoverArchivePaths = glob($capellDir . '/demo.*.zip');
    $leftoverArchives = array_filter($leftoverArchivePaths === false ? [] : $leftoverArchivePaths, is_file(...));

    expect($leftoverArchives)->toBe([]);
});

it('rejects demo zip responses with oversized content length before writing the body fallback', function (): void {
    $capellDir = storage_path('app/capell');
    $demoDir = $capellDir . '/demo';

    File::deleteDirectory($demoDir);
    $existingArchives = glob($capellDir . '/demo.*.zip');
    $existingStagingDirectories = glob($capellDir . '/demo_extract_*');
    collect($existingArchives === false ? [] : $existingArchives)->each(fn (string $path): bool => File::delete($path));
    collect($existingStagingDirectories === false ? [] : $existingStagingDirectories)->each(fn (string $path): bool => File::deleteDirectory($path));

    config()->set('capell-starter-sites.archive.max_bytes', 5);

    Http::fake([
        'https://capell.app/demo.zip' => Http::response('zip', 200, ['Content-Length' => '6']),
    ]);

    $method = new ReflectionMethod(DemoCreator::class, 'ensureStorageDemoResources');

    expect(fn (): mixed => $method->invoke(null))
        ->toThrow(Exception::class, 'maximum size');

    $leftoverArchivePaths = glob($capellDir . '/demo.*.zip');
    $leftoverArchives = array_filter($leftoverArchivePaths === false ? [] : $leftoverArchivePaths, is_file(...));

    expect($leftoverArchives)->toBe([]);
});

it('rejects demo zip symlink entries', function (): void {
    $zipPath = tempnam(sys_get_temp_dir(), 'capell-starter-sites-') . '.zip';

    $archive = new ZipArchive;
    $archive->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $archive->addFromString('demo/link', '../outside.txt');
    $archive->setExternalAttributesName('demo/link', ZipArchive::OPSYS_UNIX, 0120000 << 16);
    $archive->close();

    $archive->open($zipPath);

    $method = new ReflectionMethod(DemoCreator::class, 'assertSafeDemoZipEntries');

    expect(fn (): mixed => $method->invoke(null, $archive))
        ->toThrow(Exception::class, 'Unsafe demo zip entry');

    $archive->close();
    File::delete($zipPath);
});

it('creates a page with parent relation and media disabled', function (): void {
    File::spy();
    $demoCreator = new DemoCreator;

    $language = Language::factory()->default()->create();
    $site = Site::factory()->language($language)->default()->withTranslations($language)->create();

    $parentPage = $demoCreator->createPage([
        'name' => ['en' => 'Parent'],
        'title' => ['en' => 'Parent'],
    ], $site, createMedia: false);
    assert($parentPage instanceof Page);

    $childPage = $demoCreator->createPage([
        'name' => ['en' => 'Child'],
        'title' => ['en' => 'Child'],
    ], $site, parent: $parentPage, createMedia: false);
    assert($childPage instanceof Page);

    expect($childPage->parent_id)->toBe($parentPage->id);
    expect($childPage->getMedia()->count())->toBe(0);
});

it('falls back to a random demo image when the requested media file does not exist', function (): void {
    useTinyDemoResources();
    Queue::fake();
    Storage::fake('public');

    config()->set('media-library.disk_name', 'public');
    config()->set('media-library.conversions_disk', 'public');

    $demoCreator = new DemoCreator;

    $language = Language::factory()->default()->create();
    $site = Site::factory()->language($language)->default()->withTranslations($language)->create();
    $page = $demoCreator->createPage([
        'name' => ['en' => 'Fallback'],
        'title' => ['en' => 'Fallback'],
    ], $site, $site->languages, null, null, null, false);

    assert($page instanceof Page);

    $demoCreator->createMedia($page, 'missing-demo-image');

    $media = $page->refresh()->getFirstMedia(MediaCollectionEnum::Image->value);
    assert($media !== null);

    expect($page->getMedia(MediaCollectionEnum::Image->value))->toHaveCount(1)
        ->and($media->collection_name)->toBe(MediaCollectionEnum::Image->value)
        ->and($media->file_name)->not()->toBe('missing-demo-image.jpg');

    Storage::disk('public')->assertExists($media->getPathRelativeToRoot());
});

it('does not create duplicate media when the target collection already has media', function (): void {
    useTinyDemoResources();
    Queue::fake();
    Storage::fake('public');

    config()->set('media-library.disk_name', 'public');
    config()->set('media-library.conversions_disk', 'public');

    $demoCreator = new DemoCreator;

    $language = Language::factory()->default()->create();
    $site = Site::factory()->language($language)->default()->withTranslations($language)->create();

    $page = $demoCreator->createPage([
        'name' => ['en' => 'Existing Media'],
        'title' => ['en' => 'Existing Media'],
    ], $site, $site->languages, null, null, null, false);

    assert($page instanceof Page);

    $initialImageName = $demoCreator->getRandomDemoImage(DemoCreator::getDemoResourcePath('img'));
    $demoCreator->createMedia($page, $initialImageName);

    $initialMedia = $page->refresh()->getFirstMedia(MediaCollectionEnum::Image->value);
    assert($initialMedia !== null);

    $demoCreator->createMedia($page, 'different-demo-image');

    $currentMedia = $page->refresh()->getFirstMedia(MediaCollectionEnum::Image->value);
    assert($currentMedia !== null);

    expect($page->getMedia(MediaCollectionEnum::Image->value))->toHaveCount(1)
        ->and($currentMedia->id)->toBe($initialMedia->id)
        ->and($currentMedia->file_name)->toBe($initialMedia->file_name);
});

it('generates image conversions when creating demo media', function (): void {
    useTinyDemoResources();
    restoreRealQueue();
    Storage::fake('public');

    config()->set('media-library.disk_name', 'public');
    config()->set('media-library.conversions_disk', 'public');

    $demoCreator = new DemoCreator;

    $language = Language::factory()->default()->create();
    $site = Site::factory()->language($language)->default()->withTranslations($language)->create();

    $page = $demoCreator->createPage([
        'name' => ['en' => 'Conversions'],
        'title' => ['en' => 'Conversions'],
    ], $site, $site->languages, null, null, null, false);
    assert($page instanceof Page);

    $imageName = $demoCreator->getRandomDemoImage(DemoCreator::getDemoResourcePath('img'));
    $demoCreator->createMedia($page, $imageName);

    $media = $page->refresh()->getFirstMedia(MediaCollectionEnum::Image->value);
    assert($media !== null);

    expect(array_keys(MediaConversionEnum::defaultDimensionsByConversionValue()))
        ->toBe(MediaConversionEnum::values());

    foreach (MediaConversionEnum::cases() as $conversion) {
        expect((bool) ($media->generated_conversions[$conversion->value] ?? false))->toBeTrue();

        Storage::disk('public')->assertExists($media->getPathRelativeToRoot($conversion->value));
    }
});

function useTinyDemoResources(): void
{
    $demoDirectory = sys_get_temp_dir() . '/capell-starter-sites-resources-' . uniqid();
    $imageDirectory = $demoDirectory . '/img';
    $videoDirectory = $demoDirectory . '/video';

    File::ensureDirectoryExists($imageDirectory);
    File::ensureDirectoryExists($videoDirectory);

    $image = imagecreatetruecolor(32, 32);
    assert($image instanceof GdImage);

    $background = imagecolorallocate($image, 34, 139, 230);
    assert(is_int($background));

    imagefilledrectangle($image, 0, 0, 31, 31, $background);

    foreach (['home', 'fallback', 'existing-media', 'conversions'] as $imageName) {
        imagejpeg($image, $imageDirectory . '/' . $imageName . '.jpg', 85);
    }

    imagedestroy($image);

    File::put($videoDirectory . '/SampleVideo_1280x720_1mb.mp4', 'video');

    test()->beforeApplicationDestroyed(function () use ($demoDirectory): void {
        File::deleteDirectory($demoDirectory);
    });

    app()->instance(DemoResourceResolver::class, new class($demoDirectory)
    {
        public function __construct(private readonly string $demoDirectory) {}

        public function resolve(?string $folder): string
        {
            return $this->demoDirectory . ($folder === null || $folder === '' ? '' : '/' . ltrim($folder, '/'));
        }
    });
}

function restoreRealQueue(): void
{
    if (! Queue::isFake()) {
        return;
    }

    Queue::swap(Queue::getFacadeRoot()->queue);
}
