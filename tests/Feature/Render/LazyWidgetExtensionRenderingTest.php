<?php

declare(strict_types=1);

use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Frontend\Actions\BuildPublicPageRenderDataAction;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\Frontend\Support\Assets\FrontendResourceRegistry;
use Capell\LayoutBuilder\Actions\WidgetSnapshots\BuildPublicWidgetInteractionLocatorsAction;
use Capell\LayoutBuilder\Actions\WidgetSnapshots\RebuildPublicWidgetSnapshotsAction;
use Capell\LayoutBuilder\Actions\WidgetSnapshots\RevokePublicWidgetSnapshotsAction;
use Capell\LayoutBuilder\Http\Controllers\LazyLayoutWidgetController;
use Capell\LayoutBuilder\Models\PublicWidgetSnapshot;
use Capell\LayoutBuilder\Support\LayoutBuilderLayoutWidgetResourceUsageContributor;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Support\WidgetSnapshots\WidgetSnapshotLocatorCodec;
use Capell\LayoutBuilder\Support\WidgetSnapshots\WidgetSnapshotResourceIds;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleWidgetExtensionDefinition;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\RecordingBatchPayloadResolver;
use Illuminate\Contracts\Encryption\StringEncrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

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
            ->and($snapshot->encrypted_payload['widget']['data']['title'])->toBe('<Lazy target>');

        $htmlResponse = (new LazyLayoutWidgetController)($locator);
        expect($htmlResponse->getStatusCode())->toBe(200)
            ->and($htmlResponse->headers->get('Cache-Control'))->toContain('private', 'no-store')
            ->and($htmlResponse->getContent())->toContain('LAZY &lt;Lazy target&gt;')
            ->not->toContain('lazy-instance', 'state_version', '__capell', 'must-not-leak')
            ->and(RecordingBatchPayloadResolver::$lastLanguageCode)->toBe('cy');

        request()->headers->set('Accept', 'application/vnd.capell.widget.v2+json');
        $jsonResponse = (new LazyLayoutWidgetController)($locator);
        $json = json_decode($jsonResponse->getContent(), true, flags: JSON_THROW_ON_ERROR);
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

    $response = (new LazyLayoutWidgetController)($locator);
    expect($response->getStatusCode())->toBe(404)
        ->and($response->headers->get('Cache-Control'))->toContain('private', 'no-store')
        ->and($response->getContent())->toBe('');
})->with(['tampered', 'oversized', 'expired', 'revoked']);

it('supersedes changed revisions while retaining the previous locator through grace', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $context = lazyWidgetContext('First');
    registerLazyWidgetResources();
    $firstLocator = lazyWidgetLocator($context);

    $translation = $context->page->getRelation('translation');
    $translation->forceFill(['content' => [lazyWidgetBlock('Second')]])->save();
    $context->page->setRelation('translation', $translation->fresh());
    resolve(RebuildPublicWidgetSnapshotsAction::class)->handle($context);

    expect(PublicWidgetSnapshot::query()->count())->toBe(2)
        ->and(PublicWidgetSnapshot::query()->whereNotNull('superseded_at')->count())->toBe(1)
        ->and((new LazyLayoutWidgetController)($firstLocator)->getStatusCode())->toBe(200);

    PublicWidgetSnapshot::query()->whereNotNull('superseded_at')->update(['expires_at' => now()->subSecond()]);
    expect((new LazyLayoutWidgetController)($firstLocator)->getStatusCode())->toBe(404);
});

it('reissues an expired current snapshot immutably for an unchanged revision', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    registerLazyWidgetResources();
    $context = lazyWidgetContext('Unchanged');
    $expiredLocator = lazyWidgetLocator($context);
    PublicWidgetSnapshot::query()->update(['expires_at' => now()->subSecond()]);

    resolve(RebuildPublicWidgetSnapshotsAction::class)->handle($context);
    $freshUrls = resolve(BuildPublicWidgetInteractionLocatorsAction::class)->build($context);
    $freshLocator = rawurldecode((string) str(parse_url($freshUrls['lazy-instance'], PHP_URL_PATH))->afterLast('/'));

    expect(PublicWidgetSnapshot::query()->count())->toBe(2)
        ->and($freshLocator)->not->toBe($expiredLocator)
        ->and((new LazyLayoutWidgetController)($expiredLocator)->getStatusCode())->toBe(404)
        ->and((new LazyLayoutWidgetController)($freshLocator)->getStatusCode())->toBe(200);
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
    resolve(RebuildPublicWidgetSnapshotsAction::class)->handle($context);
    $before = PublicWidgetSnapshot::query()->count();
    app()->instance(WidgetSnapshotLocatorCodec::class, new WidgetSnapshotLocatorCodec(new class implements StringEncrypter
    {
        public function encryptString($value): string
        {
            throw new RuntimeException('Unavailable key service.');
        }

        public function decryptString($payload): string
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

        $response = (new LazyLayoutWidgetController)($locator);

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
        default => 'vendor/widget.css',
    };
    if ($mode !== 'unknown') {
        $resources->register('capell-app.widget-slideshow', 'Invalid', [[
            'source' => $source,
            'kind' => $mode === 'unsupported' ? 'image' : 'css',
        ]]);
    }
    $resources->register('capell-app.widget-slideshow.interaction', 'Valid', [['source' => 'vendor/widget.js', 'kind' => 'js']]);
    $locator = lazyWidgetLocator(lazyWidgetContext('Invalid resources'));

    expect((new LazyLayoutWidgetController)($locator)->getStatusCode())->toBe(404);
})->with(['unknown', 'cross-origin', 'wrong-scheme', 'wrong-port', 'inline', 'unsupported']);

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

    $response = (new LazyLayoutWidgetController)($locator);

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

    expect((new LazyLayoutWidgetController)($locator)->getStatusCode())->toBe(404);
});

it('supersedes snapshots for interaction targets removed by a later publication', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $context = lazyWidgetContext('Removed later');
    resolve(RebuildPublicWidgetSnapshotsAction::class)->handle($context);

    $translation = $context->page->getRelation('translation');
    $translation->forceFill(['content' => []])->save();
    $context->page->setRelation('translation', $translation->fresh());
    resolve(RebuildPublicWidgetSnapshotsAction::class)->handle($context);

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

    return new FrontendRenderContextData($page, $site, $language, $page->layout, $site->theme);
}

function lazyWidgetLocator(FrontendRenderContextData $context): string
{
    resolve(RebuildPublicWidgetSnapshotsAction::class)->handle($context);
    $url = resolve(BuildPublicWidgetInteractionLocatorsAction::class)->build($context)['lazy-instance'];

    return rawurldecode((string) str(parse_url($url, PHP_URL_PATH))->afterLast('/'));
}

function registerLazyWidgetResources(): void
{
    $resources = resolve(FrontendResourceRegistry::class);
    $resources->register('capell-app.widget-slideshow', 'Slideshow', [['source' => 'vendor/slideshow.css', 'kind' => 'css']]);
    $resources->register('capell-app.widget-slideshow.interaction', 'Slideshow interaction', [['source' => 'vendor/slideshow.js', 'kind' => 'js']]);
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
