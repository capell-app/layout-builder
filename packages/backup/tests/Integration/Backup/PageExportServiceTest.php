<?php

declare(strict_types=1);

use Capell\Backup\Data\ExportOptions;
use Capell\Backup\Services\Export\PageExportService;
use Capell\Backup\Services\Import\PackageReader;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $relativeExportDirectory = 'framework/testing/page-export-' . uniqid();
    $exportDirectory = storage_path('app/' . $relativeExportDirectory);
    File::ensureDirectoryExists($exportDirectory);
    config()->set('backup.paths.exports', $relativeExportDirectory);

    test()->exportDirectory = $exportDirectory;
});

afterEach(function (): void {
    if (isset(test()->exportDirectory) && is_string(test()->exportDirectory) && File::isDirectory(test()->exportDirectory)) {
        File::deleteDirectory(test()->exportDirectory);
    }
});

/**
 * @return array<string, string> archive path => contents
 */
function readArchiveEntries(string $zipPath): array
{
    $archive = new ZipArchive;
    $openResult = $archive->open($zipPath);

    if ($openResult !== true) {
        throw new RuntimeException(sprintf('Unable to open zip archive [%s] (code %d).', $zipPath, $openResult));
    }

    $entries = [];

    for ($index = 0; $index < $archive->numFiles; $index++) {
        $name = $archive->getNameIndex($index);
        if ($name === false) {
            continue;
        }

        $contents = $archive->getFromIndex($index);
        $entries[$name] = $contents === false ? '' : $contents;
    }

    $archive->close();

    return $entries;
}

it('exports pages to a zip archive with expected manifest and page payloads', function (): void {
    $site = Site::factory()->hasSiteDomains()->create();

    $pages = Page::factory()
        ->count(2)
        ->recycle($site)
        ->create();

    $path = (new PageExportService)->exportPages(
        $pages->modelKeys(),
        new ExportOptions(
            includeTranslations: true,
            includeMedia: false,
            includeSharedRelations: true,
            note: 'integration test export',
            includeDrafts: false,
            sourceWorkspace: null,
        ),
    );

    expect($path)->toBeString()
        ->and($path)->toEndWith('.zip')
        ->and(File::exists($path))->toBeTrue();

    $entries = readArchiveEntries($path);

    expect($entries)->toHaveKey('manifest.json')
        ->and($entries)->toHaveKey('integrity.json');

    $manifest = json_decode($entries['manifest.json'], associative: true, flags: JSON_THROW_ON_ERROR);

    expect($manifest)
        ->toHaveKey('schema_version', 1)
        ->toHaveKey('package_type', 'page-export')
        ->toHaveKey('page_count', 2)
        ->toHaveKey('site_count', 0)
        ->toHaveKey('note', 'integration test export')
        ->and($manifest['relation_counts'])->toHaveKey(Site::class)
        ->and($manifest['relation_counts'][Site::class])->toBe(1);

    $pageEntries = array_filter(
        $entries,
        fn (string $name): bool => str_starts_with($name, 'pages/') && str_ends_with($name, '.json'),
        ARRAY_FILTER_USE_KEY,
    );

    expect($pageEntries)->toHaveCount(2);

    $firstPagePayload = json_decode(reset($pageEntries), associative: true, flags: JSON_THROW_ON_ERROR);

    expect($firstPagePayload)
        ->toHaveKey('type', 'page')
        ->toHaveKey('uuid')
        ->toHaveKey('attributes')
        ->toHaveKey('shared_relations')
        ->and($firstPagePayload['shared_relations'])
        ->toHaveKey('site')
        ->and($firstPagePayload['shared_relations']['site'])
        ->toBe(['ref' => 'site:' . $site->getKey()]);
});

it('exports site domains as shared relations when shared relations are included', function (): void {
    $site = Site::factory()->hasSiteDomains(count: 2)->create();

    $page = Page::factory()->recycle($site)->create();

    $path = (new PageExportService)->exportPages(
        [$page->getKey()],
        new ExportOptions(
            includeTranslations: true,
            includeMedia: false,
            includeSharedRelations: true,
            includeDrafts: false,
        ),
    );

    $entries = readArchiveEntries($path);

    $siteEntries = array_filter(
        $entries,
        fn (string $name): bool => str_starts_with($name, 'relations/sites/') && str_ends_with($name, '.json'),
        ARRAY_FILTER_USE_KEY,
    );

    $domainEntries = array_filter(
        $entries,
        fn (string $name): bool => str_starts_with($name, 'relations/site-domains/') && str_ends_with($name, '.json'),
        ARRAY_FILTER_USE_KEY,
    );

    expect($siteEntries)->toHaveCount(1)
        ->and($domainEntries)->toHaveCount($site->siteDomains()->count());

    $manifest = json_decode($entries['manifest.json'], associative: true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['relation_counts'])
        ->toHaveKey(SiteDomain::class)
        ->and($manifest['relation_counts'][SiteDomain::class])->toBe($site->siteDomains()->count());
});

it('omits site domain relations when shared relations are disabled', function (): void {
    $site = Site::factory()->hasSiteDomains()->create();

    $page = Page::factory()->recycle($site)->create();

    $path = (new PageExportService)->exportPages(
        [$page->getKey()],
        new ExportOptions(
            includeTranslations: false,
            includeMedia: false,
            includeSharedRelations: false,
            includeDrafts: false,
        ),
    );

    $entries = readArchiveEntries($path);

    $domainEntries = array_filter(
        $entries,
        fn (string $name): bool => str_starts_with($name, 'relations/site-domains/'),
        ARRAY_FILTER_USE_KEY,
    );

    expect($domainEntries)->toBeEmpty();
});

it('writes media integrity paths that package reader can verify', function (): void {
    Storage::fake('public');

    $site = Site::factory()->hasSiteDomains()->create();
    $page = Page::factory()->recycle($site)->create();

    $media = new Media;
    $media->forceFill([
        'model_type' => $page->getMorphClass(),
        'model_id' => $page->getKey(),
        'collection_name' => 'hero',
        'name' => 'hero',
        'file_name' => 'hero.png',
        'mime_type' => 'image/png',
        'disk' => 'public',
        'conversions_disk' => 'public',
        'size' => 12,
        'manipulations' => [],
        'custom_properties' => [],
        'generated_conversions' => [],
        'responsive_images' => [],
        'order_column' => 1,
    ])->save();

    Storage::disk('public')->put($media->getKey() . '/hero.png', 'media-bytes');

    $path = (new PageExportService)->exportPages(
        [$page->getKey()],
        new ExportOptions(
            includeTranslations: true,
            includeMedia: true,
            includeSharedRelations: true,
            includeDrafts: false,
        ),
    );

    $package = (new PackageReader)->read($path);

    $integrityPaths = array_keys($package->integrity['files']);

    expect($integrityPaths)->toContain('media/' . hash('sha256', 'media-bytes') . '.png');
});

it('exports media stored by the configured media path generator', function (): void {
    Storage::fake('public');

    $site = Site::factory()->hasSiteDomains()->create();
    $page = Page::factory()->recycle($site)->create();
    $sourcePath = tempnam(sys_get_temp_dir(), 'capell-export-media-') . '.png';
    file_put_contents($sourcePath, 'stored-by-media-library');

    $page->addMedia($sourcePath)
        ->usingName('hero')
        ->usingFileName('hero.png')
        ->toMediaCollection('hero', 'public');

    $path = (new PageExportService)->exportPages(
        [$page->getKey()],
        new ExportOptions(
            includeTranslations: true,
            includeMedia: true,
            includeSharedRelations: true,
            includeDrafts: false,
        ),
    );

    $package = (new PackageReader)->read($path);

    expect(array_keys($package->integrity['files']))
        ->toContain('media/' . hash('sha256', 'stored-by-media-library') . '.png');

    @unlink($sourcePath);
});
