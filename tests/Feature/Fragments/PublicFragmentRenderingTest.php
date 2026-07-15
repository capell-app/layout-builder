<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\TranslationFactory;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\Publishing\PublishSentinel;
use Capell\Frontend\Contracts\Fragments\PublicFragmentReferenceCodec;
use Capell\Frontend\Data\Fragments\PublicFragmentReferenceData;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\CapellFrontendContext;
use Capell\Frontend\Support\Fragments\PublicFragmentUrlResolverRegistry;
use Capell\Frontend\Support\State\FrontendState;
use Capell\LayoutBuilder\Actions\Fragments\BuildLayoutBuilderFragmentReferenceAction;
use Capell\LayoutBuilder\Actions\Fragments\RenderPublicFragmentAction;
use Capell\LayoutBuilder\Contracts\PublicLayoutWidgetPayloadContributor;
use Capell\LayoutBuilder\Models\Widget;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;

it('renders a valid owner-aware public widget fragment reference', function (): void {
    $fixture = publicFragmentFixture('<section class="fragment-card">Public fragment</section>');

    expect(RenderPublicFragmentAction::run($fixture['reference']))
        ->toBe('<section class="fragment-card">Public fragment</section>');
});

it('restores the previous frontend context after rendering a public fragment', function (): void {
    $fixture = publicFragmentFixture('<section class="fragment-card">Public fragment</section>');
    $previousContext = new CapellFrontendContext(new FrontendState);

    app()->instance(CapellFrontendContext::class, $previousContext);
    Frontend::clearResolvedInstance(CapellFrontendContext::class);

    expect(RenderPublicFragmentAction::run($fixture['reference']))
        ->toBe('<section class="fragment-card">Public fragment</section>')
        ->and(resolve(CapellFrontendContext::class))->toBe($previousContext);
});

it('exposes only the named owner route with cache headers after authorization', function (): void {
    $fixture = publicFragmentFixture('<section>Route fragment</section>');
    $reference = resolve(PublicFragmentReferenceCodec::class)->decode($fixture['reference']);
    $url = resolve(PublicFragmentUrlResolverRegistry::class)->url($reference);

    $this->get($url)
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=300, public, stale-while-revalidate=60')
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertSee('Route fragment', false);
});

it('revokes references when public page eligibility changes', function (Closure $mutate): void {
    CarbonImmutable::setTestNow('2026-07-14 12:00:00');
    $fixture = publicFragmentFixture('<section>Revoked fragment</section>');
    $mutate($fixture['page']);

    expect(RenderPublicFragmentAction::run($fixture['reference']))->toBeNull();
})->with([
    'draft' => fn (Page $page) => $page->forceFill([
        'visible_from' => PublishSentinel::draftValue(),
        'visible_until' => null,
    ])->save(),
    'expired' => fn (Page $page) => $page->forceFill([
        'visible_from' => CarbonImmutable::now()->subWeek(),
        'visible_until' => CarbonImmutable::now()->subSecond(),
    ])->save(),
    'deleted' => fn (Page $page) => $page->delete(),
]);

it('revokes stale widget content and accepts its replacement reference', function (): void {
    $fixture = publicFragmentFixture('<section>Versioned fragment</section>');
    $fixture['widget']->update(['name' => 'Changed widget']);

    expect(RenderPublicFragmentAction::run($fixture['reference']))->toBeNull();

    bindPublicFragmentFrontendContext($fixture);
    $replacement = BuildLayoutBuilderFragmentReferenceAction::run('main', 1, $fixture['widget']);

    expect($replacement)->toBeString()
        ->and(RenderPublicFragmentAction::run($replacement))->toBe('<section>Versioned fragment</section>');
});

it('returns the same generic 404 without cache headers for every invalid reference', function (): void {
    $fixture = publicFragmentFixture('<section>Never rendered</section>');
    $codec = resolve(PublicFragmentReferenceCodec::class);
    $decoded = $codec->decode($fixture['reference']);
    $invalidReferences = [
        'malformed' => 'not-a-valid-token',
        'unknown owner' => $codec->encode(publicFragmentReferenceWith($decoded, owner: 'marketing')),
        'unsupported version' => encryptedInvalidFragmentToken($decoded, ['formatVersion' => 999]),
        'cross site' => $codec->encode(publicFragmentReferenceWith(
            $decoded,
            siteId: Site::factory()->withTranslations()->create()->getKey(),
        )),
        'cross language' => $codec->encode(publicFragmentReferenceWith(
            $decoded,
            languageId: Language::factory()->french()->create()->getKey(),
        )),
        'cross layout' => $codec->encode(publicFragmentReferenceWith(
            $decoded,
            ownerContext: [...$decoded->ownerContext, 'layoutId' => Layout::factory()->create()->getKey()],
        )),
    ];
    $expectedBody = null;

    foreach ($invalidReferences as $invalidReference) {
        $response = $this->get(route(
            'capell-layout-builder.fragments.show',
            ['reference' => $invalidReference],
            absolute: false,
        ));

        $response->assertNotFound()
            ->assertHeaderMissing('X-Robots-Tag');
        expect($response->headers->get('Cache-Control'))->not->toContain('public');

        $expectedBody ??= $response->getContent();
        expect($response->getContent())->toBe($expectedBody);
    }
});

it('rejects unsafe authoring surface html in public fragments', function (): void {
    $fixture = publicFragmentFixture('<section data-capell-authoring="true">Unsafe fragment</section>');

    expect(RenderPublicFragmentAction::run($fixture['reference']))->toBeNull();
});

/**
 * @return array{reference: string, language: Language, site: Site, layout: Layout, page: Page, widget: Widget}
 */
function publicFragmentFixture(string $html): array
{
    $language = Language::factory()->create(['status' => true]);
    $site = Site::factory()
        ->language($language)
        ->withTranslations($language)
        ->create(['status' => true]);
    $widget = Widget::factory()->create(['key' => 'public-fragment-widget', 'status' => true]);

    TranslationFactory::new()
        ->translatable($widget)
        ->language($language)
        ->create([
            'title' => 'Public fragment widget',
            'content' => '<p>Base content</p>',
        ]);

    $layout = Layout::factory()->site($site)->create([
        'status' => true,
        'containers' => [
            'main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]],
        ],
    ]);
    $page = Page::factory()
        ->site($site)
        ->layout($layout)
        ->withTranslations($language)
        ->create([
            'visible_from' => now()->subDay(),
            'visible_until' => null,
        ]);

    app()->singleton('test.public-fragment-payload-contributor', fn (): PublicLayoutWidgetPayloadContributor => new class($html) implements PublicLayoutWidgetPayloadContributor
    {
        public function __construct(private string $html) {}

        public function priority(): int
        {
            return 100;
        }

        public function data(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): array
        {
            return [];
        }

        public function html(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): string
        {
            return $this->html;
        }
    });
    app()->tag('test.public-fragment-payload-contributor', PublicLayoutWidgetPayloadContributor::TAG);

    $fixture = compact('language', 'site', 'layout', 'page', 'widget');
    bindPublicFragmentFrontendContext($fixture);
    $reference = BuildLayoutBuilderFragmentReferenceAction::run('main', 1, $widget);

    expect($reference)->toBeString();

    return ['reference' => $reference, ...$fixture];
}

/**
 * @param  array{language: Language, site: Site, layout: Layout, page: Page}  $fixture
 */
function bindPublicFragmentFrontendContext(array $fixture): void
{
    Frontend::clearResolvedInstance(CapellFrontendContext::class);
    app()->instance(
        CapellFrontendContext::class,
        new CapellFrontendContext(
            (new FrontendState)
                ->withSite($fixture['site'])
                ->withLanguage($fixture['language'])
                ->withPage($fixture['page'])
                ->withLayout($fixture['layout']),
        ),
    );
}

/**
 * @param  array<string, int|string>|null  $ownerContext
 */
function publicFragmentReferenceWith(
    PublicFragmentReferenceData $reference,
    ?string $owner = null,
    ?int $formatVersion = null,
    int|string|null $siteId = null,
    int|string|null $languageId = null,
    ?array $ownerContext = null,
): PublicFragmentReferenceData {
    return new PublicFragmentReferenceData(
        owner: $owner ?? $reference->owner,
        formatVersion: $formatVersion ?? $reference->formatVersion,
        pageableType: $reference->pageableType,
        pageableId: $reference->pageableId,
        siteId: $siteId ?? $reference->siteId,
        languageId: $languageId ?? $reference->languageId,
        contentVersion: $reference->contentVersion,
        ownerContext: $ownerContext ?? $reference->ownerContext,
    );
}

/**
 * @param  array<string, mixed>  $overrides
 */
function encryptedInvalidFragmentToken(PublicFragmentReferenceData $reference, array $overrides): string
{
    $payload = [
        'owner' => $reference->owner,
        'formatVersion' => $reference->formatVersion,
        'pageableType' => $reference->pageableType,
        'pageableId' => $reference->pageableId,
        'siteId' => $reference->siteId,
        'languageId' => $reference->languageId,
        'contentVersion' => $reference->contentVersion,
        'ownerContext' => $reference->ownerContext,
        ...$overrides,
    ];
    $encrypted = Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR));

    return rtrim(strtr($encrypted, '+/', '-_'), '=');
}
