<?php

declare(strict_types=1);

use Capell\BlockLibrary\Actions\BuildBuilderBlockRenderDataAction;
use Capell\BlockLibrary\Actions\ResolveBuilderBlockMediaAction;
use Capell\BlockLibrary\Support\DefaultBlockCatalog;
use Capell\BlockLibrary\Tests\Fixtures\BuilderBlockTestUrlGenerator;
use Capell\Blog\Models\Article;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\Events\Models\Event;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\Frontend\Data\PublicRenderDataCacheDependencyData;
use Capell\Frontend\Exceptions\WidgetLibraryException;
use Capell\Frontend\Support\Render\PublicRenderDataContributorRegistry;
use Capell\Frontend\Support\Render\PublicViewQueryGuard;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\BuilderBlockPublicRenderDataContributor;
use Capell\LayoutBuilder\Support\BuilderBlockRenderDataStore;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;

it('renders every saved catalog builder type through the public registry without authoring attributes', function (): void {
    foreach (array_diff(DefaultBlockCatalog::keys(), ['content']) as $type) {
        $fixture = DefaultBlockCatalog::fixturePayload($type);
        $state = [...$fixture['meta'], 'title' => 'Public heading', 'summary' => '<p>Public summary</p>', '__capell' => ['instance_id' => 'private-instance'], 'uuid' => 'private-uuid', 'wire:key' => 'private-selector', 'url' => 'javascript:alert(1)'];
        $html = view('capell-layout-builder::components.layout-widgets.index', [
            'builderBlockRenderData' => [BuildBuilderBlockRenderDataAction::run($type, $state)],
            'widgets' => [['type' => $type, 'data' => [
                ...$fixture['meta'], 'title' => 'Public heading', 'summary' => '<p>Public summary</p>',
                '__capell' => ['instance_id' => 'private-instance'], 'uuid' => 'private-uuid',
                'wire:key' => 'private-selector', 'url' => 'javascript:alert(1)',
            ]]],
            'attributes' => new ComponentAttributeBag,
        ])->render();
        $dom = new DOMDocument;
        @$dom->loadHTML('<html><body>' . $html . '</body></html>');
        $sections = new DOMXPath($dom)->query('//*[contains(concat(" ", normalize-space(@class), " "), " section " )]');
        expect($sections === false ? 0 : $sections->length)->toBeGreaterThan(0, $type);
        foreach (['private-instance', 'private-uuid', 'private-selector', '__capell', 'javascript:', 'capell-block-library', 'signed-editor'] as $forbidden) {
            expect($html)->not->toContain($forbidden);
        }
    }
});

it('hydrates owner scoped Spatie images for logos and team and caches only the public fragment', function (string $type, string $items): void {
    config(['filesystems.disks.public.visibility' => 'public']);
    $owner = Page::factory()->create();
    $key = (string) Str::uuid();
    $collection = ResolveBuilderBlockMediaAction::collection($key);
    $ownerTranslation = Translation::factory()->translatable($owner)->create();
    $owner->setRelation('translation', $ownerTranslation);
    $image = Media::factory()->model($ownerTranslation)->create([
        'collection_name' => $collection, 'disk' => 'public', 'mime_type' => 'image/png', 'file_name' => 'portrait.png',
    ]);
    $foreign = Media::factory()->model(Translation::factory()->translatable(Page::factory()->create())->create())->create([
        'collection_name' => $collection, 'disk' => 'public', 'mime_type' => 'image/png',
    ]);
    $preparedState = ['media_key' => $key, $items => [
        'private-repeater-uuid' => ['name' => 'Ada', 'image' => $image->uuid],
        'other-repeater-uuid' => ['name' => 'Grace', 'image' => $foreign->uuid],
    ]];
    builderBlockPreparedDataStore()->put($owner, $type, $preparedState, BuildBuilderBlockRenderDataAction::run($type, $preparedState, ResolveBuilderBlockMediaAction::run($owner, $key)));
    $html = view('capell-layout-builder::components.layout-widgets.index', [
        'page' => $owner,
        'widgets' => [['type' => $type, 'data' => ['media_key' => $key, $items => [
            'private-repeater-uuid' => ['name' => 'Ada', 'image' => $image->uuid],
            'other-repeater-uuid' => ['name' => 'Grace', 'image' => $foreign->uuid],
        ]]]],
        'attributes' => new ComponentAttributeBag,
    ])->render();
    Cache::put('builder-public-fragment', $html);
    $fragment = Cache::get('builder-public-fragment');
    expect($fragment)->toBe($html);
    $dom = new DOMDocument;
    @$dom->loadHTML('<html><body>' . $html . '</body></html>');
    expect($dom->getElementsByTagName('img'))->toHaveCount(1)
        ->and($dom->getElementsByTagName('img')->item(0)?->getAttribute('src'))->toBe($image->getUrl());
    foreach ([$key, $image->uuid, $foreign->uuid, 'private-repeater-uuid', 'other-repeater-uuid', 'media_key', 'block_images'] as $forbidden) {
        expect($html)->not->toContain($forbidden);
    }
})->with(['logos' => ['logos', 'logos'], 'team' => ['team', 'members']]);

it('resolves Widget translation images when Foundation renders nested content', function (): void {
    config(['filesystems.disks.public.visibility' => 'public']);
    $widget = Widget::factory()->create();
    $page = Page::factory()->create();
    $key = (string) Str::uuid();
    $widgetTranslation = Translation::factory()->translatable($widget)->create();
    $widget->setRelation('translation', $widgetTranslation);
    $image = Media::factory()->model($widgetTranslation)->create([
        'collection_name' => ResolveBuilderBlockMediaAction::collection($key),
        'disk' => 'public', 'mime_type' => 'image/png', 'file_name' => 'portrait.png',
    ]);
    $state = ['media_key' => $key, 'members' => [['name' => 'Ada', 'image' => $image->uuid]]];
    builderBlockPreparedDataStore()->put($widget, 'team', $state, BuildBuilderBlockRenderDataAction::run('team', $state, ResolveBuilderBlockMediaAction::run($widget, $key)));
    $html = view('capell-theme-foundation::components.content', [
        'pageRecord' => $page, 'blockMediaOwner' => $widget,
        'contentType' => ContentStructure::Blocks,
        'content' => [['type' => 'team', 'data' => ['media_key' => $key, 'members' => [['name' => 'Ada', 'image' => $image->uuid]]]]],
        'attributes' => new ComponentAttributeBag,
    ])->render();
    $dom = new DOMDocument;
    @$dom->loadHTML('<html><body>' . $html . '</body></html>');
    expect($dom->getElementsByTagName('img'))->toHaveCount(1)
        ->and($dom->getElementsByTagName('img')->item(0)?->getAttribute('src'))->toBe($image->getUrl());
    expect($html)->not->toContain($key)->not->toContain($image->uuid)->not->toContain('block-media-owner')->not->toContain('blockMediaOwner');
});

it('rejects private non-image missing and foreign-collection media', function (): void {
    $page = Page::factory()->create();
    $key = (string) Str::uuid();
    $collection = ResolveBuilderBlockMediaAction::collection($key);
    $translation = Translation::factory()->translatable($page)->create();
    $page->setRelation('translation', $translation);
    foreach ([['disk' => 'local', 'mime_type' => 'image/png', 'collection_name' => $collection],
        ['disk' => 'public', 'mime_type' => 'application/pdf', 'collection_name' => $collection],
        ['disk' => 'public', 'mime_type' => 'image/png', 'collection_name' => 'another-block']] as $attributes) {
        Media::factory()->model($translation)->create($attributes);
    }

    expect(ResolveBuilderBlockMediaAction::run($page, $key))->toBe([])
        ->and(ResolveBuilderBlockMediaAction::run($page, 'invalid-key'))->toBe([])
        ->and(ResolveBuilderBlockMediaAction::run(null, $key))->toBe([]);
});

it('prepares media before guarded public rendering and declares media and alt-text cache dependencies', function (): void {
    config(['filesystems.disks.public.visibility' => 'public', 'capell-frontend.public_view_query_guard.enabled' => true, 'capell-frontend.public_view_query_guard.mode' => 'exception']);
    $language = Language::factory()->create();
    $page = Page::factory()->create(['content_structure_override' => ContentStructure::Blocks->value]);
    $key = (string) Str::uuid();
    $translation = Translation::factory()->translatable($page)->create();
    $page->setRelation('translation', $translation);
    $image = Media::factory()->model($translation)->create([
        'collection_name' => ResolveBuilderBlockMediaAction::collection($key),
        'disk' => 'public', 'mime_type' => 'image/png', 'file_name' => 'logo.png',
    ]);
    $alt = Translation::factory()->translatable($image)->create(['meta' => ['alt' => 'Public logo']]);
    $blocks = [['type' => 'logos', 'data' => ['media_key' => $key, 'logos' => [['name' => 'Ada', 'image' => $image->uuid]]]]];
    $translation->update(['content' => json_encode($blocks, JSON_THROW_ON_ERROR)]);
    $page->setRelation('translation', $translation);
    $context = new FrontendRenderContextData($page, null, $language, null, null);
    $contributor = resolve(PublicRenderDataContributorRegistry::class)->all()['builder_blocks'];
    $metadata = $contributor->metadata($context);
    $dependencies = array_map(static fn (PublicRenderDataCacheDependencyData $dependency): string => $dependency->identity(), $metadata->cacheDependencies);
    expect($dependencies)->toContain($image::class . ':' . $image->getKey(), $alt::class . ':' . $alt->getKey());
    $html = resolve(PublicViewQueryGuard::class)->guard($context, static fn (): string => view('capell-layout-builder::components.layout-widgets.index', [
        'page' => $page, 'widgets' => $blocks, 'attributes' => new ComponentAttributeBag,
    ])->render());
    throw_unless(is_string($html), RuntimeException::class);
    $dom = new DOMDocument;
    @$dom->loadHTML('<html><body>' . $html . '</body></html>');
    expect($dom->getElementsByTagName('img')->item(0)?->getAttribute('alt'))->toBe('Public logo');
    expect(json_encode($contributor->contribute($context)->value, JSON_THROW_ON_ERROR))->not->toContain($key)->not->toContain($image->uuid)->not->toContain('media_key');
});

it('prepares every Pageable context before guarded rendering including leftover non-media fields', function (string $modelClass): void {
    config(['filesystems.disks.public.visibility' => 'public', 'capell-frontend.public_view_query_guard.enabled' => true, 'capell-frontend.public_view_query_guard.mode' => 'exception']);
    $page = new $modelClass;
    throw_unless($page instanceof Model && $page instanceof Pageable, LogicException::class);
    $page->setRawAttributes(['id' => 901, 'content_structure_override' => ContentStructure::Blocks->value]);
    $page->setRelation('blueprint', Blueprint::factory()->page()->create(['meta' => ['content_structure' => ContentStructure::Blocks->value]]));

    $translation = Translation::factory()->translatable(Page::factory()->create(['content_structure_override' => ContentStructure::Blocks->value]))->create();
    $key = (string) Str::uuid();
    $image = Media::factory()->model($translation)->create(['collection_name' => ResolveBuilderBlockMediaAction::collection($key), 'disk' => 'public', 'mime_type' => 'image/png']);
    $blocks = [
        ['type' => 'hero', 'data' => ['title' => 'Pageable heading', 'media_key' => (string) Str::uuid()]],
        ['type' => 'logos', 'data' => ['media_key' => $key, 'logos' => [['name' => 'Partner', 'image' => $image->uuid]]]],
    ];
    $translation->update(['content' => json_encode($blocks, JSON_THROW_ON_ERROR)]);
    $page->setRelation('translation', $translation);
    $context = new FrontendRenderContextData($page, null, Language::factory()->create(), null, null);
    $contributor = resolve(PublicRenderDataContributorRegistry::class)->all()['builder_blocks'];
    expect($contributor->supports($context))->toBeTrue();
    $contributor->contribute($context);
    $html = resolve(PublicViewQueryGuard::class)->guard($context, static fn (): string => view('capell-layout-builder::components.layout-widgets.index', [
        'page' => $page, 'widgets' => $blocks, 'attributes' => new ComponentAttributeBag,
    ])->render());
    expect($html)->toContain('Pageable heading', $image->getUrl());
})->with([Article::class, Event::class, Page::class]);

it('never queries from the composer even when media was not prepared', function (): void {
    $page = Page::factory()->create();
    $context = new FrontendRenderContextData($page, null, null, null, null);
    config(['capell-frontend.public_view_query_guard.enabled' => true, 'capell-frontend.public_view_query_guard.mode' => 'exception']);
    DB::enableQueryLog();
    DB::flushQueryLog();
    expect(fn () => resolve(PublicViewQueryGuard::class)->guard($context, static fn (): string => view('capell-layout-builder::components.layout-widgets.index', [
        'page' => $page, 'widgets' => [['type' => 'hero', 'data' => ['title' => 'Safe text', 'media_key' => (string) Str::uuid()]]], 'attributes' => new ComponentAttributeBag,
    ])->render()))->toThrow(WidgetLibraryException::class, "Render data for builder block 'hero' was not prepared.");
    expect(DB::getQueryLog())->toBe([]);
    DB::disableQueryLog();
});

it('hydrates all block metadata with a fixed query count', function (): void {
    config(['filesystems.disks.public.visibility' => 'public']);
    $counts = [];
    foreach ([1, 5] as $count) {
        $page = Page::factory()->create(['content_structure_override' => ContentStructure::Blocks->value]);
        $translation = Translation::factory()->translatable($page)->create();
        $blocks = [];
        foreach (range(1, $count) as $index) {
            $key = (string) Str::uuid();
            $image = Media::factory()->model($translation)->create(['collection_name' => ResolveBuilderBlockMediaAction::collection($key), 'disk' => 'public', 'mime_type' => 'image/png']);
            Translation::factory()->translatable($image)->create(['meta' => ['alt' => 'Logo ' . $index]]);
            $blocks[] = ['type' => 'logos', 'data' => ['media_key' => $key, 'logos' => [['name' => 'Partner', 'image' => $image->uuid]]]];
        }

        $translation->update(['content' => json_encode($blocks, JSON_THROW_ON_ERROR)]);
        $page->setRelation('translation', $translation);
        $context = new FrontendRenderContextData($page, null, null, null, null);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $contribution = (new BuilderBlockPublicRenderDataContributor)->contribute($context);
        $counts[] = count(DB::getQueryLog());
        DB::disableQueryLog();
        expect(get_object_vars($contribution->value)['blocks'])->toHaveCount($count);
    }

    expect($counts[1])->toBe($counts[0])->and($counts[0])->toBeGreaterThan(0);
});

it('batches media metadata across several distinct widget owners as well as block collections', function (): void {
    config(['filesystems.disks.public.visibility' => 'public']);
    $counts = [];
    foreach ([[1, 1], [1, 5], [4, 5]] as [$ownerCount, $blockCount]) {
        $page = Page::factory()->create(['content_structure_override' => ContentStructure::Blocks->value]);
        $page->load('blueprint');
        $pageTranslation = Translation::factory()->translatable($page)->create(['content' => '[]']);
        $page->setRelation('translation', $pageTranslation);
        $widgets = [];
        foreach (range(1, $ownerCount) as $ownerIndex) {
            $widget = Widget::factory()->create();
            $widget->blueprint->update(['meta' => ['content_structure' => ContentStructure::Blocks->value]]);
            $translation = Translation::factory()->translatable($widget)->create();
            $blocks = [];
            foreach (range(1, $blockCount) as $index) {
                $key = (string) Str::uuid();
                $image = Media::factory()->model($translation)->create(['collection_name' => ResolveBuilderBlockMediaAction::collection($key), 'disk' => 'public', 'mime_type' => 'image/png']);
                Translation::factory()->translatable($image)->create(['meta' => ['alt' => 'Logo']]);
                $blocks[] = ['type' => 'logos', 'data' => ['media_key' => $key, 'logos' => [['name' => 'Partner', 'image' => $image->uuid]]]];
            }

            $translation->update(['content' => json_encode($blocks, JSON_THROW_ON_ERROR)]);
            $widget->setRelation('translation', $translation);
            $widgets[$widget->key] = $widget;
        }

        $layout = new Layout(['containers' => ['main' => ['widgets' => array_map(static fn (string $key): array => ['widget_key' => $key, 'occurrence' => 1], array_keys($widgets))]]]);
        $loader = Mockery::mock(LayoutLoader::class);
        $loader->shouldReceive('preloadLayoutWidgets')->once();
        $loader->shouldReceive('getLayoutWidget')->andReturnUsing(static fn ($layout, string $key): Collection|Widget => $widgets[$key]);
        app()->instance(LayoutLoader::class, $loader);
        $context = new FrontendRenderContextData($page, null, Language::factory()->create(), $layout, null);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $contribution = (new BuilderBlockPublicRenderDataContributor)->contribute($context);
        $counts[] = count(DB::getQueryLog());
        DB::disableQueryLog();
        expect(get_object_vars($contribution->value)['blocks'])->toHaveCount($ownerCount * $blockCount);
    }

    expect($counts)->toBe([3, 3, 3]);
});

function builderBlockPreparedDataStore(): BuilderBlockRenderDataStore
{
    $contributor = resolve(PublicRenderDataContributorRegistry::class)->all()['builder_blocks'];
    throw_unless($contributor instanceof BuilderBlockPublicRenderDataContributor, LogicException::class);

    return $contributor->renderData();
}

it('retains real Spatie versioned public media URLs', function (): void {
    config(['filesystems.disks.public.visibility' => 'public', 'media-library.version_urls' => true]);
    $translation = Translation::factory()->translatable(Page::factory()->create())->create();
    $key = (string) Str::uuid();
    $image = Media::factory()->model($translation)->create([
        'collection_name' => ResolveBuilderBlockMediaAction::collection($key), 'disk' => 'public', 'mime_type' => 'image/png', 'file_name' => 'versioned.png',
    ]);
    $url = $image->getUrl();
    expect($url)->toContain('?v=');
    expect(ResolveBuilderBlockMediaAction::run($translation, $key))->toHaveKey($image->uuid)
        ->and(ResolveBuilderBlockMediaAction::run($translation, $key)[$image->uuid]['url'])->toBe($url);
});

it('uses the shared credential policy for CDN media parameters', function (string $url, bool $allowed): void {
    config(['filesystems.disks.public.visibility' => 'public', 'media-library.url_generator' => BuilderBlockTestUrlGenerator::class, 'testing.builder_block_media_url' => $url]);
    $translation = Translation::factory()->translatable(Page::factory()->create())->create();
    $key = (string) Str::uuid();
    $image = Media::factory()->model($translation)->create([
        'collection_name' => ResolveBuilderBlockMediaAction::collection($key), 'disk' => 'public', 'mime_type' => 'image/png',
    ]);
    $images = ResolveBuilderBlockMediaAction::run($translation, $key);
    expect($images)->toHaveCount($allowed ? 1 : 0);
    if ($allowed) {
        expect($images[$image->uuid]['url'])->toBe($url);
    }
})->with([
    ['https://cdn.example.test/image?v=123456&width=1200&height=800&fit=cover&format=webp&quality=80&dpr=2', true],
    ['/photo?caption=%23&access_token=private', false],
    ['/photo?caption=%2523&X-Amz-Signature=private', false],
    ['/photo?caption=%23&credentials%5Btoken%5D=private', false],
    ['#access_token=private', false],
    ['/photo?accessToken=private', false],
    ['/photo?redirect=https%3A%2F%2Fexample.test%2Fimage%3Faccess_token%3Dprivate', false],
]);
