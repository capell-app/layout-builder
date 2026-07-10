<?php

declare(strict_types=1);

use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Actions\WidgetSnapshots\BuildPublicWidgetInteractionLocatorsAction;
use Capell\LayoutBuilder\Actions\WidgetSnapshots\RebuildPublicWidgetSnapshotsAction;
use Capell\LayoutBuilder\Actions\WidgetSnapshots\RevokePublicWidgetSnapshotsAction;
use Capell\LayoutBuilder\Http\Controllers\LazyLayoutWidgetController;
use Capell\LayoutBuilder\Models\PublicWidgetSnapshot;
use Capell\LayoutBuilder\Support\LayoutBuilderLayoutWidgetResourceUsageContributor;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleWidgetExtensionDefinition;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\RecordingBatchPayloadResolver;
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
        $url = resolve(BuildPublicWidgetInteractionLocatorsAction::class)->build($context)['lazy-instance'];
        $locator = rawurldecode((string) str(parse_url($url, PHP_URL_PATH))->afterLast('/'));
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
    $url = resolve(BuildPublicWidgetInteractionLocatorsAction::class)->build($context)['lazy-instance'];
    $locator = rawurldecode((string) str(parse_url($url, PHP_URL_PATH))->afterLast('/'));

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
    $firstUrl = resolve(BuildPublicWidgetInteractionLocatorsAction::class)->build($context)['lazy-instance'];
    $firstLocator = rawurldecode((string) str(parse_url($firstUrl, PHP_URL_PATH))->afterLast('/'));

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
