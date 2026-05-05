<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Migrator\Services\Import\PackageReadResult;
use Capell\Migrator\Services\Import\PageImportService;
use Capell\Migrator\Services\Import\ResolutionMap;
use Capell\Migrator\Services\Import\Resolvers\MatchResolution;
use Illuminate\Support\Str;

/**
 * @param  array<string, mixed>  $overrides
 */
function makePageDescriptor(Layout $layout, Type $type, Site $site, int $sourceId = 9001, array $overrides = []): string
{
    $attributes = array_merge([
        'id' => $sourceId,
        'uuid' => (string) Str::uuid(),
        'name' => 'Imported Page ' . $sourceId,
        'layout_id' => $layout->getKey(),
        'type_id' => $type->getKey(),
        'site_id' => $site->getKey(),
        'parent_id' => null,
    ], $overrides);

    $descriptor = [
        'type' => 'page',
        'uuid' => $attributes['uuid'],
        'id' => $sourceId,
        'attributes' => $attributes,
        'owned_relations' => ['page_urls' => []],
        'shared_relations' => [
            'layout' => ['ref' => 'layout:' . $layout->getKey()],
            'type' => ['ref' => 'type:' . $type->getKey()],
            'site' => ['ref' => 'site:' . $site->getKey()],
        ],
        'media_bindings' => [],
    ];

    return (string) json_encode($descriptor);
}

function fullyResolvedMap(Layout $layout, Type $type, Site $site): ResolutionMap
{
    return new ResolutionMap(
        resolved: [
            'layout:' . $layout->getKey() => new MatchResolution(localId: (int) $layout->getKey(), strategy: 'key'),
            'type:' . $type->getKey() => new MatchResolution(localId: (int) $type->getKey(), strategy: 'key'),
            'site:' . $site->getKey() => new MatchResolution(localId: (int) $site->getKey(), strategy: 'slug'),
        ],
        unresolved: [],
    );
}

it('imports a page and rewrites shared refs', function (): void {
    $layout = Layout::factory()->create();
    $type = Type::factory()->create();
    $site = Site::factory()->create();

    $package = new PackageReadResult(
        archivePath: '',
        manifest: [],
        integrity: [],
        payload: ['pages/abc.json' => makePageDescriptor($layout, $type, $site)],
    );

    $report = (new PageImportService)->import($package, fullyResolvedMap($layout, $type, $site));

    expect($report->errors)->toBe([])
        ->and($report->isSuccess())->toBeTrue()
        ->and($report->pagesCreated)->toBe(1)
        ->and($report->pagesSkipped)->toBe(0);

    $page = Page::query()->withoutGlobalScopes()->whereKey($report->createdPageIds[0])->first();
    expect($page)->not->toBeNull()
        ->and((int) $page->getAttribute('layout_id'))->toBe((int) $layout->getKey())
        ->and((int) $page->getAttribute('site_id'))->toBe((int) $site->getKey());
});

it('remaps parent_id to the local id when both parent and child are imported', function (): void {
    $layout = Layout::factory()->create();
    $type = Type::factory()->create();
    $site = Site::factory()->create();

    $parentJson = makePageDescriptor($layout, $type, $site, sourceId: 501);
    $childJson = makePageDescriptor($layout, $type, $site, sourceId: 502, overrides: ['parent_id' => 501]);

    $package = new PackageReadResult(
        archivePath: '',
        manifest: [],
        integrity: [],
        payload: [
            'pages/parent.json' => $parentJson,
            'pages/child.json' => $childJson,
        ],
    );

    $report = (new PageImportService)->import($package, fullyResolvedMap($layout, $type, $site));

    expect($report->pagesCreated)->toBe(2);

    $pages = Page::query()->withoutGlobalScopes()->whereIn('id', $report->createdPageIds)->get();

    $child = $pages->first(fn (Page $page): bool => $page->getAttribute('parent_id') !== null);
    $parent = $pages->first(fn (Page $page): bool => $page->getAttribute('parent_id') === null);

    expect($child)->not->toBeNull()
        ->and($parent)->not->toBeNull()
        ->and((int) $child->getAttribute('parent_id'))->toBe((int) $parent->getKey());
});

it('skips pages whose shared relations are unresolved', function (): void {
    $layout = Layout::factory()->create();
    $type = Type::factory()->create();
    $site = Site::factory()->create();

    $package = new PackageReadResult(
        archivePath: '',
        manifest: [],
        integrity: [],
        payload: ['pages/orphan.json' => makePageDescriptor($layout, $type, $site)],
    );

    $emptyMap = new ResolutionMap(resolved: [], unresolved: ['layout:' . $layout->getKey()]);

    $report = (new PageImportService)->import($package, $emptyMap);

    expect($report->pagesCreated)->toBe(0)
        ->and($report->pagesSkipped)->toBe(1);
});

it('rebinds media owners to the newly imported page', function (): void {
    $layout = Layout::factory()->create();
    $type = Type::factory()->create();
    $site = Site::factory()->create();

    $holder = Page::factory()->create();
    $media = new Media;
    $media->forceFill([
        'model_type' => $holder->getMorphClass(),
        'model_id' => $holder->getKey(),
        'collection_name' => 'hero',
        'name' => 'img',
        'file_name' => 'img.png',
        'mime_type' => 'image/png',
        'disk' => 'public',
        'conversions_disk' => 'public',
        'size' => 10,
        'manipulations' => [],
        'custom_properties' => [],
        'generated_conversions' => [],
        'responsive_images' => [],
        'order_column' => 1,
    ])->save();

    $descriptor = json_decode(makePageDescriptor($layout, $type, $site), true);
    $descriptor['media_bindings'] = [
        ['collection' => 'hero', 'ref' => 'media:' . $media->getKey()],
    ];

    $map = new ResolutionMap(
        resolved: [
            'layout:' . $layout->getKey() => new MatchResolution(localId: (int) $layout->getKey(), strategy: 'key'),
            'type:' . $type->getKey() => new MatchResolution(localId: (int) $type->getKey(), strategy: 'key'),
            'site:' . $site->getKey() => new MatchResolution(localId: (int) $site->getKey(), strategy: 'slug'),
            'media:' . $media->getKey() => new MatchResolution(localId: (int) $media->getKey(), strategy: 'checksum'),
        ],
        unresolved: [],
    );

    $package = new PackageReadResult(
        archivePath: '',
        manifest: [],
        integrity: [],
        payload: ['pages/media.json' => (string) json_encode($descriptor)],
    );

    $report = (new PageImportService)->import($package, $map);

    expect($report->mediaReassigned)->toBe(1);

    $media->refresh();
    expect((int) $media->getAttribute('model_id'))->toBe((int) $report->createdPageIds[0])
        ->and($media->getAttribute('collection_name'))->toBe('hero');
});

it('restores imported page urls onto the newly imported page', function (): void {
    $layout = Layout::factory()->create();
    $type = Type::factory()->create();
    $site = Site::factory()->withTranslations()->create();
    $otherSite = Site::factory()->withTranslations()->create();
    $language = $site->language;
    $sourceId = 9001;

    $existingLocalPage = Page::factory()
        ->site($site)
        ->type($type)
        ->layout($layout)
        ->create(['id' => $sourceId]);

    $descriptor = json_decode(makePageDescriptor($layout, $type, $site, sourceId: $sourceId), true, 512, JSON_THROW_ON_ERROR);
    $descriptor['owned_relations']['page_urls'] = [
        [
            'id' => 12345,
            'site_id' => $otherSite->getKey(),
            'language_id' => $language->getKey(),
            'pageable_type' => $existingLocalPage->getMorphClass(),
            'pageable_id' => $sourceId,
            'url' => '/imported-page',
            'target_url' => null,
            'status_code' => 301,
            'is_manual' => false,
            'notes' => 'Imported URL',
            'type' => null,
            'status' => true,
            'created_by' => 999,
            'updated_by' => 999,
            'deleted_by' => 999,
            'created_at' => '2001-01-01 00:00:00',
            'updated_at' => '2001-01-01 00:00:00',
            'deleted_at' => '2001-01-01 00:00:00',
        ],
    ];

    $package = new PackageReadResult(
        archivePath: '',
        manifest: [],
        integrity: [],
        payload: ['pages/with-url.json' => json_encode($descriptor, JSON_THROW_ON_ERROR)],
    );

    $report = (new PageImportService)->import($package, fullyResolvedMap($layout, $type, $site));

    expect($report->errors)->toBe([])
        ->and($report->pageUrlsCreated)->toBe(1);

    $importedPageId = $report->createdPageIds[0];
    $pageUrl = PageUrl::query()->where('url', '/imported-page')->firstOrFail();

    expect((int) $pageUrl->getAttribute('pageable_id'))->toBe((int) $importedPageId)
        ->and($pageUrl->getAttribute('pageable_type'))->toBe((new Page)->getMorphClass())
        ->and((int) $pageUrl->getAttribute('site_id'))->toBe((int) $site->getKey())
        ->and((int) $pageUrl->getAttribute('pageable_id'))->not->toBe((int) $existingLocalPage->getKey())
        ->and($pageUrl->getAttribute('created_by'))->not->toBe(999)
        ->and($pageUrl->trashed())->toBeFalse();
});

it('does not import internal page state from package attributes', function (): void {
    $layout = Layout::factory()->create();
    $type = Type::factory()->create();
    $site = Site::factory()->create();

    $package = new PackageReadResult(
        archivePath: '',
        manifest: [],
        integrity: [],
        payload: [
            'pages/internal-state.json' => makePageDescriptor(
                $layout,
                $type,
                $site,
                overrides: [
                    '_lft' => 777,
                    '_rgt' => 888,
                    'created_by' => 999,
                    'updated_by' => 999,
                    'deleted_by' => 999,
                ],
            ),
        ],
    );

    $report = (new PageImportService)->import($package, fullyResolvedMap($layout, $type, $site));

    $page = Page::query()->withoutGlobalScopes()->whereKey($report->createdPageIds[0])->firstOrFail();

    expect((int) $page->getAttribute('_lft'))->not->toBe(777)
        ->and((int) $page->getAttribute('_rgt'))->not->toBe(888)
        ->and($page->getAttribute('created_by'))->not->toBe(999)
        ->and($page->getAttribute('updated_by'))->not->toBe(999)
        ->and($page->getAttribute('deleted_by'))->not->toBe(999);
});
