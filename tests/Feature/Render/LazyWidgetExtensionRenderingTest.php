<?php

declare(strict_types=1);

use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Theme;
use Capell\Frontend\Actions\BuildPublicPageRenderDataAction;
use Capell\Frontend\Data\Assets\FrontendResourceData;
use Capell\Frontend\Data\Assets\FrontendResourceGroupData;
use Capell\Frontend\Data\Assets\PublicResourceSourceData;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\Frontend\Support\Assets\FrontendResourceRegistry;
use Capell\LayoutBuilder\Actions\LayoutWidgets\RenderLazyLayoutWidgetAction;
use Capell\LayoutBuilder\Actions\WidgetSnapshots\BuildPublicWidgetInteractionLocatorsAction;
use Capell\LayoutBuilder\Actions\WidgetSnapshots\RebuildPublicWidgetSnapshotsAction;
use Capell\LayoutBuilder\Actions\WidgetSnapshots\RevokePublicWidgetSnapshotsAction;
use Capell\LayoutBuilder\Contracts\WidgetSnapshots\WidgetSnapshotLocatorCipher;
use Capell\LayoutBuilder\Models\PublicWidgetSnapshot;
use Capell\LayoutBuilder\Support\LayoutBuilderLayoutWidgetResourceUsageContributor;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Support\WidgetSnapshots\WidgetSnapshotLocatorCodec;
use Capell\LayoutBuilder\Support\WidgetSnapshots\WidgetSnapshotResourceIds;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleWidgetExtensionDefinition;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\RecordingBatchPayloadResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function (): void {
    RecordingBatchPayloadResolver::$calls = 0;
    RecordingBatchPayloadResolver::$mode = 'valid';
    RecordingBatchPayloadResolver::$lastLanguageCode = null;
});

it('stores an immutable encrypted snapshot and renders HTML and registry-owned V2 resources', function (): void {
    $viewRoot = sys_get_temp_dir() . '/capell-lazy-widget-' . bin2hex(random_bytes(6));
    mkdir($viewRoot, 0777, true);
    file_put_contents($viewRoot . '/widget.blade.php', '<article>LAZY {{ $widget->title }}</article>');
    View::addNamespace('lazy-widget-test', $viewRoot);

    try {
        resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make(
            fallbackView: 'lazy-widget-test::widget',
            batchPayloadResolver: RecordingBatchPayloadResolver::class,
        ));
        config()->set('capell-frontend.public_view_query_guard.enabled', true);
        config()->set('capell-frontend.public_view_query_guard.mode', 'exception');
        $context = lazyWidgetContext('<Lazy target>');
        registerLazyWidgetResources();
        $locator = lazyWidgetLocator($context);
        $snapshot = PublicWidgetSnapshot::query()->sole();
        $rawPayload = DB::table('public_widget_snapshots')->value('encrypted_payload');

        expect($rawPayload)->toBeString()->not->toContain('Lazy target', 'lazy-instance')
            ->and(data_get($snapshot->encrypted_payload, 'widget.data.title'))->toBe('<Lazy target>');

        $htmlResponse = lazyWidgetResponse($locator);
        expect($htmlResponse->getStatusCode())->toBe(200)
            ->and($htmlResponse->headers->get('Cache-Control'))->toContain('private', 'no-store')
            ->and($htmlResponse->getContent())->toContain('LAZY &lt;Lazy target&gt;')
            ->not->toContain('lazy-instance', 'state_version', '__capell', 'must-not-leak')
            ->and(RecordingBatchPayloadResolver::$lastLanguageCode)->toBe('cy');

        request()->headers->set('Accept', 'application/vnd.capell.widget.v2+json');
        $jsonResponse = lazyWidgetResponse($locator);
        $json = json_decode((string) $jsonResponse->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $json = is_array($json) ? $json : [];
        expect($json)->toMatchArray([
            'version' => 2,
            'status' => 'ok',
            'resource_ids' => [
                LayoutBuilderLayoutWidgetResourceUsageContributor::resourceGroupPublicId('capell-app.widget-slideshow'),
                LayoutBuilderLayoutWidgetResourceUsageContributor::resourceGroupPublicId('capell-app.widget-slideshow.interaction'),
            ],
        ])->and($json['html'])->toContain('LAZY &lt;Lazy target&gt;')
            ->and($jsonResponse->getContent())->not->toContain('capell-app', 'widget-slideshow')
            ->and($jsonResponse->headers->get('Cache-Control'))->toContain('private', 'no-store');
    } finally {
        @unlink($viewRoot . '/widget.blade.php');
        @rmdir($viewRoot);
    }
});

it('rejects tampered oversized expired and revoked locators with the same generic response', function (string $mode): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $context = lazyWidgetContext('Never render');
    registerLazyWidgetResources();
    $locator = lazyWidgetLocator($context);

    if ($mode === 'tampered') {
        $offset = intdiv(strlen($locator), 2);
        $locator[$offset] = $locator[$offset] === 'a' ? 'b' : 'a';
    } elseif ($mode === 'oversized') {
        $locator = str_repeat('a', 2049);
    } elseif ($mode === 'expired') {
        PublicWidgetSnapshot::query()->update(['expires_at' => now()->subSecond()]);
    } else {
        RevokePublicWidgetSnapshotsAction::run($context->page);
    }

    $response = lazyWidgetResponse($locator);
    expect($response->getStatusCode())->toBe(404)
        ->and($response->headers->get('Cache-Control'))->toContain('private', 'no-store')
        ->and($response->getContent())->toBe('');
})->with(['tampered', 'oversized', 'expired', 'revoked']);

it('supersedes changed revisions while retaining the previous locator through grace', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $context = lazyWidgetContext('First');
    registerLazyWidgetResources();
    $firstLocator = lazyWidgetLocator($context);
    $firstSnapshot = PublicWidgetSnapshot::query()->sole();
    $stableCurrentKey = $firstSnapshot->current_key;
    $firstFingerprint = $firstSnapshot->context_fingerprint;

    if ($context->page === null) {
        throw new RuntimeException('Expected a render context page.');
    }
    $translation = $context->page->getRelation('translation');
    if (! $translation instanceof Model) {
        throw new RuntimeException('Expected a page translation.');
    }
    $translation->forceFill(['content' => [lazyWidgetBlock('Second')]])->save();
    $context->page->setRelation('translation', $translation->fresh());
    RebuildPublicWidgetSnapshotsAction::run($context);
    $current = PublicWidgetSnapshot::query()->whereNotNull('current_key')->sole();
    $historical = PublicWidgetSnapshot::query()->whereNotNull('superseded_at')->sole();

    expect(PublicWidgetSnapshot::query()->count())->toBe(2)
        ->and($current->current_key)->toBe($stableCurrentKey)
        ->and($current->context_fingerprint)->not->toBe($firstFingerprint)
        ->and($historical->current_key)->toBeNull()
        ->and(PublicWidgetSnapshot::query()->whereNull('superseded_at')->count())->toBe(1)
        ->and(lazyWidgetResponse($firstLocator)->getStatusCode())->toBe(200);

    PublicWidgetSnapshot::query()->whereNotNull('superseded_at')->update(['expires_at' => now()->subSecond()]);
    expect(lazyWidgetResponse($firstLocator)->getStatusCode())->toBe(404);
});

it('keeps the immutable current snapshot available without expiry until superseded', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    registerLazyWidgetResources();
    $context = lazyWidgetContext('Unchanged');
    $locator = lazyWidgetLocator($context);
    $snapshot = PublicWidgetSnapshot::query()->sole();

    $this->travel(30)->days();
    RebuildPublicWidgetSnapshotsAction::run($context);

    expect($snapshot->expires_at)->toBeNull()
        ->and(PublicWidgetSnapshot::query()->count())->toBe(1)
        ->and(lazyWidgetResponse($locator)->getStatusCode())->toBe(200);
});

it('enforces one database-backed current row while repeated rebuilds remain idempotent', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $context = lazyWidgetContext('Idempotent');

    RebuildPublicWidgetSnapshotsAction::run($context);
    RebuildPublicWidgetSnapshotsAction::run($context);
    $snapshot = PublicWidgetSnapshot::query()->sole();

    expect($snapshot->current_key)->toBeString()
        ->and($snapshot->expires_at)->toBeNull()
        ->and(fn () => PublicWidgetSnapshot::query()->create([
            ...$snapshot->only($snapshot->getFillable()),
            'context_fingerprint' => str_repeat('a', 64),
        ]))->toThrow(QueryException::class);
});

it('retries a unique race and supersedes a different-fingerprint competitor', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $context = lazyWidgetContext('Race winner');
    $injected = false;
    PublicWidgetSnapshot::creating(function (PublicWidgetSnapshot $snapshot) use (&$injected): void {
        if ($injected) {
            return;
        }
        $injected = true;
        DB::table('public_widget_snapshots')->insert([
            ...$snapshot->getAttributes(),
            'context_fingerprint' => str_repeat('c', 64),
            'owner_revision' => str_repeat('d', 64),
        ]);
    });

    try {
        $result = RebuildPublicWidgetSnapshotsAction::run($context);
        $requested = $result['lazy-instance'];
        $current = PublicWidgetSnapshot::query()->whereNotNull('current_key')->sole();
        $competitor = PublicWidgetSnapshot::query()->whereNotNull('superseded_at')->sole();

        expect($injected)->toBeTrue()
            ->and(PublicWidgetSnapshot::query()->count())->toBe(2)
            ->and(PublicWidgetSnapshot::query()->whereNull('superseded_at')->count())->toBe(1)
            ->and($current->is($requested))->toBeTrue()
            ->and($current->context_fingerprint)->not->toBe(str_repeat('c', 64))
            ->and($competitor->context_fingerprint)->toBe(str_repeat('c', 64))
            ->and($competitor->current_key)->toBeNull();
    } finally {
        PublicWidgetSnapshot::flushEventListeners();
    }
});

it('keeps public render-data generation read-only and fails open when no snapshot exists', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $context = lazyWidgetContext('Read only');
    $writes = [];
    DB::listen(function ($query) use (&$writes): void {
        if (preg_match('/^(insert|update|delete)/i', ltrim($query->sql)) === 1) {
            $writes[] = $query->sql;
        }
    });

    $renderData = BuildPublicPageRenderDataAction::run($context);

    expect($renderData->widgetInteractionLocators)->toBe([])
        ->and(PublicWidgetSnapshot::query()->count())->toBe(0)
        ->and($writes)->toBe([]);
});

it('fails open without writes when locator encryption fails during ordinary public rendering', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $context = lazyWidgetContext('Encryption failure');
    RebuildPublicWidgetSnapshotsAction::run($context);
    $before = PublicWidgetSnapshot::query()->count();
    app()->instance(WidgetSnapshotLocatorCodec::class, new WidgetSnapshotLocatorCodec(new class implements WidgetSnapshotLocatorCipher
    {
        public function encrypt(string $plaintext): string
        {
            throw new RuntimeException('Unavailable key service.');
        }

        public function decrypt(string $ciphertext): string
        {
            throw new RuntimeException('Unavailable key service.');
        }
    }));
    app()->forgetInstance(BuildPublicWidgetInteractionLocatorsAction::class);

    $renderData = BuildPublicPageRenderDataAction::run($context);

    expect($renderData->widgetInteractionLocators)->toBe([])
        ->and(PublicWidgetSnapshot::query()->count())->toBe($before);
});

it('rejects unsafe package or theme HTML for both response formats', function (string $accept): void {
    $viewRoot = sys_get_temp_dir() . '/capell-unsafe-lazy-' . bin2hex(random_bytes(6));
    mkdir($viewRoot, 0777, true);
    file_put_contents($viewRoot . '/widget.blade.php', '<article data-capell-editor="secret">Unsafe</article>');
    View::addNamespace('unsafe-lazy-widget', $viewRoot);

    try {
        resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make(
            fallbackView: 'unsafe-lazy-widget::widget',
            batchPayloadResolver: RecordingBatchPayloadResolver::class,
        ));
        registerLazyWidgetResources();
        $locator = lazyWidgetLocator(lazyWidgetContext('Unsafe'));
        request()->headers->set('Accept', $accept);

        $response = lazyWidgetResponse($locator);

        expect($response->getStatusCode())->toBe(404)
            ->and($response->getContent())->toBe('');
    } finally {
        @unlink($viewRoot . '/widget.blade.php');
        @rmdir($viewRoot);
    }
})->with(['text/html', 'application/vnd.capell.widget.v2+json']);

it('rejects unknown unsafe cross-origin inline and unsupported resource groups', function (string $mode): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $resources = new FrontendResourceRegistry;
    app()->instance(FrontendResourceRegistry::class, $resources);
    app()->forgetInstance(WidgetSnapshotResourceIds::class);
    $source = match ($mode) {
        'cross-origin' => 'http://external.test/widget.css',
        'wrong-scheme' => 'https://localhost/widget.css',
        'wrong-port' => 'http://localhost:9999/widget.css',
        'inline' => 'data:text/css,body{}',
        'raw-code' => 'alert(1)',
        'traversal' => 'vendor/../secret.css',
        'encoded-traversal' => 'vendor/%2e%2e/secret.css',
        'whitespace' => 'vendor/widget style.css',
        'wrong-extension' => 'vendor/widget.js',
        default => 'vendor/widget.css',
    };
    if ($mode !== 'unknown') {
        registerLazyWidgetResourceGroup(
            $resources,
            'capell-app.widget-slideshow',
            'Invalid',
            $source,
            $mode === 'unsupported' ? 'image' : 'css',
        );
    }
    registerLazyWidgetResourceGroup($resources, 'capell-app.widget-slideshow.interaction', 'Valid', 'vendor/widget.js', 'js');
    $locator = lazyWidgetLocator(lazyWidgetContext('Invalid resources'));

    expect(lazyWidgetResponse($locator)->getStatusCode())->toBe(404);
})->with([
    'unknown', 'cross-origin', 'wrong-scheme', 'wrong-port', 'inline', 'unsupported',
    'raw-code', 'traversal', 'encoded-traversal', 'whitespace', 'wrong-extension',
]);

it('rejects replay after any bound public context or revision field changes', function (string $field): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    registerLazyWidgetResources();
    $context = lazyWidgetContext('Bound context');
    $locator = lazyWidgetLocator($context);
    $snapshot = PublicWidgetSnapshot::query()->sole();
    $value = match ($field) {
        'site_id' => Site::factory()->createOne()->getKey(),
        'language_id' => Language::factory()->createOne(['code' => 'fr'])->getKey(),
        'layout_id' => Layout::factory()->createOne()->getKey(),
        'theme_id' => Theme::factory()->createOne()->getKey(),
        'render_profile' => 'static-export',
        default => str_repeat('f', 64),
    };
    $snapshot->forceFill([$field => $value])->save();

    $response = lazyWidgetResponse($locator);

    expect($response->getStatusCode())->toBe(404)
        ->and($response->getContent())->toBe('');
})->with(['site_id', 'language_id', 'layout_id', 'theme_id', 'render_profile', 'owner_revision']);

it('rejects a directly encrypted locator with the wrong purpose', function (): void {
    $payload = json_encode([
        'version' => 1,
        'purpose' => 'poll-vote',
        'snapshotId' => 1,
        'pageableType' => 'page',
        'pageableId' => 1,
        'targetInstanceId' => 'target',
    ], JSON_THROW_ON_ERROR);
    $ciphertext = resolve('encrypter')->encryptString($payload);
    $locator = 'v1.' . rtrim(strtr(base64_encode($ciphertext), '+/', '-_'), '=');

    expect(lazyWidgetResponse($locator)->getStatusCode())->toBe(404);
});

it('rejects an intact locator replayed on a different incoming host', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    registerLazyWidgetResources();
    $locator = lazyWidgetLocator(lazyWidgetContext('Tenant bound'));
    app()->instance('request', Request::create('http://evil.test/_capell/layout-widgets/' . $locator));

    expect(lazyWidgetResponse($locator)->getStatusCode())->toBe(404);
});

it('rejects an intact locator through a domain path bound to another language', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    registerLazyWidgetResources();
    $context = lazyWidgetContext('Locale bound');
    if ($context->site === null) {
        throw new RuntimeException('Expected a render context site.');
    }
    $locator = lazyWidgetLocator($context);
    $otherLanguage = Language::factory()->createOne(['code' => 'fr']);
    SiteDomain::query()->create([
        'site_id' => $context->site->id,
        'language_id' => $otherLanguage->id,
        'domain' => 'localhost',
        'scheme' => 'http',
        'path' => '/_capell',
        'status' => true,
        'default' => false,
    ]);
    app()->instance('request', Request::create('http://localhost/_capell/layout-widgets/' . $locator));

    expect(lazyWidgetResponse($locator)->getStatusCode())->toBe(404);
});

it('generates locators against the resolved language-domain origin and path', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $context = lazyWidgetContext('Domain path');
    if ($context->site === null || $context->language === null) {
        throw new RuntimeException('Expected render context site and language.');
    }
    SiteDomain::query()->where('site_id', $context->site->id)->update(['status' => false]);
    SiteDomain::query()->create([
        'site_id' => $context->site->id,
        'language_id' => $context->language->id,
        'domain' => 'example.test',
        'scheme' => 'https',
        'path' => '/cy',
        'status' => true,
        'default' => true,
    ]);
    app()->instance('request', Request::create('https://example.test/cy/page'));
    RebuildPublicWidgetSnapshotsAction::run($context);

    $url = resolve(BuildPublicWidgetInteractionLocatorsAction::class)->build($context)['lazy-instance'];

    expect($url)->toStartWith('https://example.test/cy/_capell/layout-widgets/');
});

it('preserves the effective incoming port in generated locator origins', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $context = lazyWidgetContext('Domain port');
    app()->instance('request', Request::create('http://localhost:8080/page'));
    RebuildPublicWidgetSnapshotsAction::run($context);

    $url = resolve(BuildPublicWidgetInteractionLocatorsAction::class)->build($context)['lazy-instance'];

    expect($url)->toStartWith('http://localhost:8080/_capell/layout-widgets/');
});

it('routes a localized path-domain locator through the explicit HTTP endpoint before the public fallback', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    registerLazyWidgetResources();
    $context = lazyWidgetContext('Localized route');
    if ($context->site === null || $context->language === null) {
        throw new RuntimeException('Expected render context site and language.');
    }
    SiteDomain::query()->where('site_id', $context->site->id)->update(['status' => false]);
    SiteDomain::query()->create([
        'site_id' => $context->site->id,
        'language_id' => $context->language->id,
        'domain' => 'localhost',
        'scheme' => 'http',
        'path' => '/cy',
        'status' => true,
        'default' => true,
    ]);
    app()->instance('request', Request::create('http://localhost/cy/page'));
    RebuildPublicWidgetSnapshotsAction::run($context);
    $url = resolve(BuildPublicWidgetInteractionLocatorsAction::class)->build($context)['lazy-instance'];
    $path = (string) parse_url($url, PHP_URL_PATH);

    expect(Route::has('capell-layout-builder.layout-widgets.localized.show'))->toBeTrue()
        ->and(Route::getRoutes()->match(Request::create($path))->getName())
        ->toBe('capell-layout-builder.layout-widgets.localized.show');

    $response = $this->get($path)->assertOk();
    expect($response->baseResponse->headers->get('Cache-Control'))->toContain('private', 'no-store');
});

it('supersedes snapshots for interaction targets removed by a later publication', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $context = lazyWidgetContext('Removed later');
    RebuildPublicWidgetSnapshotsAction::run($context);

    if ($context->page === null) {
        throw new RuntimeException('Expected a render context page.');
    }
    $translation = $context->page->getRelation('translation');
    if (! $translation instanceof Model) {
        throw new RuntimeException('Expected a page translation.');
    }
    $translation->forceFill(['content' => []])->save();
    $context->page->setRelation('translation', $translation->fresh());
    RebuildPublicWidgetSnapshotsAction::run($context);

    expect(PublicWidgetSnapshot::query()->sole()->superseded_at)->not->toBeNull();
});

function lazyWidgetContext(string $title): FrontendRenderContextData
{
    $language = Language::factory()->createOne(['code' => 'cy']);
    $site = Site::factory()->createOne(['language_id' => $language->id]);
    $page = Page::factory()
        ->site($site)
        ->state(['content_structure_override' => ContentStructure::Blocks->value])
        ->withTranslations($language, ['title' => 'Widget', 'content' => [lazyWidgetBlock($title)]], slug: '/widget', contentStructure: ContentStructure::Blocks)
        ->createOne();
    $page->setRelation('translation', $page->translations()->firstOrFail());
    SiteDomain::query()->updateOrCreate([
        'site_id' => $site->id,
        'domain' => 'localhost',
        'path' => null,
    ], [
        'scheme' => 'http',
        'language_id' => $language->id,
        'status' => true,
        'default' => true,
    ]);

    return new FrontendRenderContextData($page, $site, $language, $page->layout, $site->theme);
}

function lazyWidgetLocator(FrontendRenderContextData $context): string
{
    RebuildPublicWidgetSnapshotsAction::run($context);
    $url = resolve(BuildPublicWidgetInteractionLocatorsAction::class)->build($context)['lazy-instance'];
    $path = parse_url($url, PHP_URL_PATH);

    return rawurldecode((string) str(is_string($path) ? $path : '')->afterLast('/'));
}

function lazyWidgetResponse(string $locator): Response
{
    return RenderLazyLayoutWidgetAction::run($locator)
        ?? response('', Response::HTTP_NOT_FOUND, ['Cache-Control' => 'private, no-store']);
}

function registerLazyWidgetResources(): void
{
    $resources = resolve(FrontendResourceRegistry::class);
    registerLazyWidgetResourceGroup($resources, 'capell-app.widget-slideshow', 'Slideshow', 'vendor/slideshow.css', 'css');
    registerLazyWidgetResourceGroup($resources, 'capell-app.widget-slideshow.interaction', 'Slideshow interaction', 'vendor/slideshow.js', 'js');
}

function registerLazyWidgetResourceGroup(
    FrontendResourceRegistry $registry,
    string $key,
    string $label,
    string $source,
    string $kind,
): void {
    try {
        $resourceSource = new PublicResourceSourceData($source);
        $handle = 'capell-app/layout-builder:' . hash('xxh128', $key . ':' . $source);
        $resource = match ($kind) {
            'css' => FrontendResourceData::style($handle, 'capell-app/layout-builder', $resourceSource),
            'js' => FrontendResourceData::moduleScript($handle, 'capell-app/layout-builder', $resourceSource),
            default => null,
        };

        if (! $resource instanceof FrontendResourceData) {
            return;
        }

        $registry->register(new FrontendResourceGroupData(
            key: $key,
            label: $label,
            package: 'capell-app/layout-builder',
            resources: [$resource],
        ));
    } catch (Throwable) {
        // Invalid definitions intentionally remain unregistered and fail closed.
    }
}

/** @return array<string, mixed> */
function lazyWidgetBlock(string $title): array
{
    return [
        'type' => 'capell-app.slideshow',
        'data' => [
            'title' => $title,
            '__capell' => [
                'instance_id' => 'lazy-instance',
                'state_version' => 2,
                'editor_url' => 'must-not-leak',
            ],
        ],
    ];
}
