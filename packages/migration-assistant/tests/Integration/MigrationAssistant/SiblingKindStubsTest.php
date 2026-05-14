<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Theme;
use Capell\Core\Models\Type;
use Capell\MigrationAssistant\Services\Import\PackageReadResult;
use Capell\MigrationAssistant\Services\Import\ResolutionMap;
use Capell\MigrationAssistant\Services\Import\Resolvers\MatchResolution;
use Capell\MigrationAssistant\Services\Import\SiteImportService;
use Illuminate\Support\Str;

it('imports a site package and delegates page writes through imported site refs', function (): void {
    $language = Language::factory()->english()->create();
    $type = Type::factory()->site()->create();
    $theme = Theme::factory()->create();
    $layout = Layout::factory()->create();
    $pageType = Type::factory()->page()->create();
    $sourceSiteId = 901;
    $sourceDomainId = 902;
    $sourcePageId = 903;

    $package = new PackageReadResult(
        archivePath: '',
        manifest: [],
        integrity: [],
        payload: [
            'relations/sites/source.json' => json_encode([
                'type' => 'site',
                'ref' => 'site:' . $sourceSiteId,
                'id' => $sourceSiteId,
                'attributes' => [
                    'name' => 'Imported Site',
                    'type_id' => $type->getKey(),
                    'theme_id' => $theme->getKey(),
                    'language_id' => $language->getKey(),
                    'status' => true,
                    'default' => false,
                ],
            ], JSON_THROW_ON_ERROR),
            'relations/site-domains/source.json' => json_encode([
                'type' => 'site-domain',
                'ref' => 'site-domain:' . $sourceDomainId,
                'id' => $sourceDomainId,
                'attributes' => [
                    'site_id' => $sourceSiteId,
                    'language_id' => $language->getKey(),
                    'domain' => 'imported.example.com',
                    'scheme' => 'https',
                    'path' => null,
                    'status' => true,
                    'default' => true,
                ],
            ], JSON_THROW_ON_ERROR),
            'pages/source.json' => json_encode([
                'type' => 'page',
                'uuid' => (string) Str::uuid(),
                'id' => $sourcePageId,
                'attributes' => [
                    'id' => $sourcePageId,
                    'uuid' => (string) Str::uuid(),
                    'name' => 'Imported Home',
                    'layout_id' => $layout->getKey(),
                    'type_id' => $pageType->getKey(),
                    'site_id' => $sourceSiteId,
                    'parent_id' => null,
                ],
                'owned_relations' => ['page_urls' => []],
                'shared_relations' => [
                    'layout' => ['ref' => 'layout:' . $layout->getKey()],
                    'type' => ['ref' => 'type:' . $pageType->getKey()],
                    'site' => ['ref' => 'site:' . $sourceSiteId],
                ],
                'media_bindings' => [],
            ], JSON_THROW_ON_ERROR),
        ],
    );

    $report = (new SiteImportService)->import($package, new ResolutionMap(
        resolved: [
            'layout:' . $layout->getKey() => new MatchResolution(localId: (int) $layout->getKey(), strategy: 'key'),
            'type:' . $pageType->getKey() => new MatchResolution(localId: (int) $pageType->getKey(), strategy: 'key'),
        ],
        unresolved: ['site:' . $sourceSiteId],
    ));

    $site = Site::query()->where('name', 'Imported Site')->firstOrFail();
    $page = Page::query()->withoutGlobalScopes()->where('name', 'Imported Home')->firstOrFail();
    $domain = SiteDomain::query()->where('domain', 'imported.example.com')->firstOrFail();

    expect($report->isSuccess())->toBeTrue()
        ->and($report->pagesCreated)->toBe(1)
        ->and((int) $page->getAttribute('site_id'))->toBe((int) $site->getKey())
        ->and((int) $domain->getAttribute('site_id'))->toBe((int) $site->getKey());
});

it('does not import site domains with unmapped source site ids', function (): void {
    $language = Language::factory()->english()->create();
    $existingSite = Site::factory()->create();
    $sourceSiteId = $existingSite->getKey();

    $package = new PackageReadResult(
        archivePath: '',
        manifest: [],
        integrity: [],
        payload: [
            'relations/site-domains/source.json' => json_encode([
                'type' => 'site-domain',
                'ref' => 'site-domain:777',
                'id' => 777,
                'attributes' => [
                    'site_id' => $sourceSiteId,
                    'language_id' => $language->getKey(),
                    'domain' => 'unmapped-import.example.com',
                    'scheme' => 'https',
                    'path' => '/',
                    'status' => true,
                    'default' => true,
                ],
            ], JSON_THROW_ON_ERROR),
        ],
    );

    $report = (new SiteImportService)->import($package, new ResolutionMap(
        resolved: [],
        unresolved: [],
    ));

    expect($report->createdSiteDomainIds)->toBe([])
        ->and(SiteDomain::query()->where('domain', 'unmapped-import.example.com')->exists())->toBeFalse();
});

it('rejects imported site domains that conflict with existing public domains', function (): void {
    $language = Language::factory()->english()->create();
    $type = Type::factory()->site()->create();
    $theme = Theme::factory()->create();
    $existingSite = Site::factory()->create();
    SiteDomain::factory()->create([
        'site_id' => $existingSite->getKey(),
        'domain' => 'conflict.example.com',
        'scheme' => 'https',
        'path' => '/',
    ]);

    $sourceSiteId = 901;
    $package = new PackageReadResult(
        archivePath: '',
        manifest: [],
        integrity: [],
        payload: [
            'relations/sites/source.json' => json_encode([
                'type' => 'site',
                'ref' => 'site:' . $sourceSiteId,
                'id' => $sourceSiteId,
                'attributes' => [
                    'name' => 'Conflicting Imported Site',
                    'type_id' => $type->getKey(),
                    'theme_id' => $theme->getKey(),
                    'language_id' => $language->getKey(),
                    'status' => true,
                    'default' => false,
                ],
            ], JSON_THROW_ON_ERROR),
            'relations/site-domains/source.json' => json_encode([
                'type' => 'site-domain',
                'ref' => 'site-domain:902',
                'id' => 902,
                'attributes' => [
                    'site_id' => $sourceSiteId,
                    'language_id' => $language->getKey(),
                    'domain' => 'conflict.example.com',
                    'scheme' => 'https',
                    'path' => '/',
                    'status' => true,
                    'default' => true,
                ],
            ], JSON_THROW_ON_ERROR),
        ],
    );

    (new SiteImportService)->import($package, new ResolutionMap(
        resolved: [],
        unresolved: ['site:' . $sourceSiteId],
    ));
})->throws(RuntimeException::class, 'Refusing to import conflicting site domain [conflict.example.com].');
