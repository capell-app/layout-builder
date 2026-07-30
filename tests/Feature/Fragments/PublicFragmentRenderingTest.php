<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\TranslationFactory;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\Publishing\PublishSentinel;
use Capell\Frontend\Actions\AssertPublicHtmlContainsNoAuthoringSurfaceAction;
use Capell\Frontend\Actions\Fragments\ResolvePublicFragmentContentVersionAction;
use Capell\Frontend\Contracts\Fragments\PublicFragmentReferenceCodec;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Data\Fragments\PublicFragmentReferenceData;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Fragments\PublicFragmentUrlResolverRegistry;
use Capell\Frontend\Support\State\FrontendState;
use Capell\LayoutBuilder\Actions\BuildPublicLayoutGraphAction;
use Capell\LayoutBuilder\Actions\Fragments\BuildLayoutBuilderFragmentReferenceAction;
use Capell\LayoutBuilder\Actions\Fragments\RenderPublicFragmentAction;
use Capell\LayoutBuilder\Contracts\PublicLayoutWidgetPayloadContributor;
use Capell\LayoutBuilder\Data\PublicFragmentRenderResultData;
use Capell\LayoutBuilder\Enums\PublicFragmentRenderOutcome;
use Capell\LayoutBuilder\Models\Widget;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Psr\Log\LogLevel;

it('renders a valid owner-aware public widget fragment reference', function (): void {
    $fixture = publicFragmentFixture('<section class="fragment-card">Public fragment</section>');

    expect(RenderPublicFragmentAction::run($fixture['reference']))
        ->toBe('<section class="fragment-card">Public fragment</section>');
});

it('turns an unexpected codec failure into one structured safe failure', function (): void {
    app()->instance(PublicFragmentReferenceCodec::class, throwingPublicFragmentReferenceCodec());
    $log = Log::spy();

    $result = RenderPublicFragmentAction::make()->result('opaque-reference');

    expect($result->outcome)->toBe(PublicFragmentRenderOutcome::RenderFailed)
        ->and($result->html)->toBeNull();

    $log->shouldHaveReceived('log')
        ->once()
        ->with(
            LogLevel::ERROR,
            'Public layout fragment did not render.',
            Mockery::on(static fn (array $context): bool => $context['outcome'] === 'render_failed'
                && ($context['exception'] ?? null) instanceof RuntimeException
                && ! array_key_exists('owner_context', $context)),
        );
});

it('answers an unexpected codec failure with an empty 500 response', function (): void {
    config(['app.debug' => true]);
    app()->instance(PublicFragmentReferenceCodec::class, throwingPublicFragmentReferenceCodec());

    $this->get(publicFragmentUrl('opaque-reference'))
        ->assertStatus(500)
        ->assertContent('')
        ->assertHeaderMissing('X-Robots-Tag');
});

it('logs only correlation-safe reference fields after decoding', function (): void {
    $fixture = publicFragmentFixture('<section>Never rendered</section>');
    $reference = publicFragmentReferenceWithOwnerContext($fixture, [
        'containerKey' => '',
        'privateDiagnostic' => 'must-not-be-logged',
    ]);
    $log = Log::spy();

    RenderPublicFragmentAction::make()->result($reference);

    $log->shouldHaveReceived('log')
        ->once()
        ->with(
            LogLevel::DEBUG,
            'Public layout fragment did not render.',
            Mockery::on(static fn (array $context): bool => array_keys($context) === [
                'outcome',
                'owner',
                'format_version',
                'pageable_id',
                'site_id',
                'language_id',
            ] && ! in_array('must-not-be-logged', $context, true)),
        );
});

it('enforces public fragment render result invariants', function (): void {
    expect(fn (): PublicFragmentRenderResultData => PublicFragmentRenderResultData::rendered('   '))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn (): PublicFragmentRenderResultData => PublicFragmentRenderResultData::failed(
            PublicFragmentRenderOutcome::Rendered,
        ))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn (): PublicFragmentRenderResultData => new PublicFragmentRenderResultData(
            PublicFragmentRenderOutcome::EmptyHtml,
            '<section>Contradictory HTML</section>',
        ))
        ->toThrow(InvalidArgumentException::class);
});

it('restores the previous frontend context after rendering a public fragment', function (): void {
    $fixture = publicFragmentFixture('<section class="fragment-card">Public fragment</section>');
    $previousContext = new FrontendState;

    app()->instance(FrontendContextReader::class, $previousContext);
    Frontend::clearResolvedInstance(FrontendContextReader::class);

    expect(RenderPublicFragmentAction::run($fixture['reference']))
        ->toBe('<section class="fragment-card">Public fragment</section>')
        ->and(resolve(FrontendContextReader::class))->toBe($previousContext);
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

    bindPublicFragmentFrontendContext([
        'language' => $fixture['language'],
        'site' => $fixture['site'],
        'layout' => $fixture['layout'],
        'page' => $fixture['page'],
    ]);
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
        'blank container key' => publicFragmentReferenceWithOwnerContext($fixture, ['containerKey' => '']),
        'blank widget key' => publicFragmentReferenceWithOwnerContext($fixture, ['widgetKey' => '']),
        'unparsable occurrence' => publicFragmentReferenceWithOwnerContext($fixture, ['occurrence' => 'first']),
        'blank widget version' => publicFragmentReferenceWithOwnerContext($fixture, ['widgetVersion' => '']),
        'unknown widget key' => publicFragmentReferenceWithOwnerContext($fixture, ['widgetKey' => 'missing-widget']),
    ];
    foreach ($invalidReferences as $invalidReference) {
        $response = $this->get(route(
            'capell-layout-builder.fragments.show',
            ['reference' => $invalidReference],
            absolute: false,
        ));

        $response->assertNotFound()
            ->assertHeaderMissing('X-Robots-Tag');
        expect($response->baseResponse->headers->get('Cache-Control'))->not->toContain('public');
        expect($response->getContent())->toBe('');
    }
});

it('rejects unsafe authoring surface html in public fragments', function (): void {
    $fixture = publicFragmentFixture('<section data-capell-authoring="true">Unsafe fragment</section>');

    expect(RenderPublicFragmentAction::run($fixture['reference']))->toBeNull();
});

it('reports the specific outcome for every non-rendering fragment path', function (PublicFragmentRenderOutcome $expected): void {
    $fixture = publicFragmentFixture('<section>Outcome fragment</section>');

    $reference = match ($expected) {
        PublicFragmentRenderOutcome::InvalidReference => 'not-a-valid-token',
        PublicFragmentRenderOutcome::ForeignOwner => (function () use ($fixture): string {
            $codec = resolve(PublicFragmentReferenceCodec::class);

            return $codec->encode(publicFragmentReferenceWith(
                $codec->decode($fixture['reference']),
                owner: 'marketing',
            ));
        })(),
        PublicFragmentRenderOutcome::ContextUnavailable => (function () use ($fixture): string {
            $fixture['page']->delete();

            return $fixture['reference'];
        })(),
        PublicFragmentRenderOutcome::MissingContainerKey => publicFragmentReferenceWithOwnerContext(
            $fixture,
            ['containerKey' => ''],
        ),
        PublicFragmentRenderOutcome::MissingWidgetKey => publicFragmentReferenceWithOwnerContext(
            $fixture,
            ['widgetKey' => ''],
        ),
        PublicFragmentRenderOutcome::MissingOccurrence => publicFragmentReferenceWithOwnerContext(
            $fixture,
            ['occurrence' => 'first'],
        ),
        PublicFragmentRenderOutcome::MissingWidgetVersion => publicFragmentReferenceWithOwnerContext(
            $fixture,
            ['widgetVersion' => ''],
        ),
        PublicFragmentRenderOutcome::WidgetUnavailable => (function () use ($fixture): string {
            $fixture['widget']->update(['status' => false]);

            return $fixture['reference'];
        })(),
        PublicFragmentRenderOutcome::WidgetVersionMismatch => (function () use ($fixture): string {
            $fixture['widget']->update(['name' => 'Changed widget']);

            return $fixture['reference'];
        })(),
        default => throw new LogicException("Outcome [{$expected->value}] is not covered by this dataset."),
    };

    $result = RenderPublicFragmentAction::make()->result($reference);

    expect($result->outcome)->toBe($expected)
        ->and($result->html)->toBeNull()
        ->and($result->outcome->httpStatus())->toBe(404);

    $this->get(publicFragmentUrl($reference))
        ->assertNotFound()
        ->assertContent('');
})->with([
    'invalid reference' => PublicFragmentRenderOutcome::InvalidReference,
    'foreign owner' => PublicFragmentRenderOutcome::ForeignOwner,
    'context unavailable' => PublicFragmentRenderOutcome::ContextUnavailable,
    'missing container key' => PublicFragmentRenderOutcome::MissingContainerKey,
    'missing widget key' => PublicFragmentRenderOutcome::MissingWidgetKey,
    'missing occurrence' => PublicFragmentRenderOutcome::MissingOccurrence,
    'missing widget version' => PublicFragmentRenderOutcome::MissingWidgetVersion,
    'widget unavailable' => PublicFragmentRenderOutcome::WidgetUnavailable,
    'widget version mismatch' => PublicFragmentRenderOutcome::WidgetVersionMismatch,
]);

it('reports blank fragment html as a not-found outcome and answers 404', function (): void {
    $fixture = publicFragmentFixture('   ');
    $result = RenderPublicFragmentAction::make()->result($fixture['reference']);

    expect($result->outcome)->toBe(PublicFragmentRenderOutcome::EmptyHtml)
        ->and($result->html)->toBeNull();

    $this->get(publicFragmentUrl($fixture['reference']))
        ->assertNotFound()
        ->assertContent('');
});

it('fails soft with a 404 when rendered fragment html contains an authoring surface', function (): void {
    $fixture = publicFragmentFixture('<section data-capell-authoring="true">Unsafe fragment</section>');
    $result = RenderPublicFragmentAction::make()->result($fixture['reference']);

    expect($result->outcome)->toBe(PublicFragmentRenderOutcome::AuthoringSurfaceRejected)
        ->and($result->outcome->httpStatus())->toBe(404)
        ->and($result->html)->toBeNull();

    $response = $this->get(publicFragmentUrl($fixture['reference']));
    $body = (string) $response->getContent();

    $response->assertNotFound()->assertHeaderMissing('X-Robots-Tag');
    expect($body)->toBe('');
});

it('answers a crashed fragment render with a bare 500 that leaks no internals', function (): void {
    config(['app.debug' => false]);
    $fixture = publicFragmentFixture('<section>Never rendered</section>');

    BuildPublicLayoutGraphAction::shouldRun()
        ->andThrow(new RuntimeException('Widget renderer exploded in App\\Secret\\Renderer::html()'));

    $result = RenderPublicFragmentAction::make()->result($fixture['reference']);

    expect($result->outcome)->toBe(PublicFragmentRenderOutcome::RenderFailed)
        ->and($result->outcome->isUnexpectedFailure())->toBeTrue()
        ->and($result->outcome->httpStatus())->toBe(500)
        ->and($result->html)->toBeNull()
        ->and(RenderPublicFragmentAction::run($fixture['reference']))->toBeNull();

    $response = $this->get(publicFragmentUrl($fixture['reference']));
    $body = (string) $response->getContent();

    $response->assertStatus(500)->assertHeaderMissing('X-Robots-Tag');
    AssertPublicHtmlContainsNoAuthoringSurfaceAction::run($response->baseResponse);
    expect($response->baseResponse->headers->get('Cache-Control'))->not->toContain('public');
    expect($body)->toBe('');
});

it('keeps anonymous fragment responses free of authoring surfaces for every outcome', function (): void {
    $rendered = publicFragmentFixture('<section>Anonymous fragment</section>');
    $renderedResponse = $this->get(publicFragmentUrl($rendered['reference']));

    $renderedResponse->assertOk();
    AssertPublicHtmlContainsNoAuthoringSurfaceAction::run($renderedResponse->baseResponse);

    $notFoundResponse = $this->get(publicFragmentUrl('not-a-valid-token'));

    $notFoundResponse->assertNotFound();
    AssertPublicHtmlContainsNoAuthoringSurfaceAction::run($notFoundResponse->baseResponse);

    expect(auth()->check())->toBeFalse();
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

    bindPublicFragmentFrontendContext([
        'language' => $language,
        'site' => $site,
        'layout' => $layout,
        'page' => $page,
    ]);
    $reference = BuildLayoutBuilderFragmentReferenceAction::run('main', 1, $widget);

    if (! is_string($reference)) {
        throw new RuntimeException('Expected the public fragment reference builder to return a string.');
    }

    if (! $language instanceof Language
        || ! $site instanceof Site
        || ! $layout instanceof Layout
        || ! $page instanceof Page
        || ! $widget instanceof Widget) {
        throw new RuntimeException('Expected public fragment factories to return their declared model types.');
    }

    return [
        'reference' => $reference,
        'language' => $language,
        'site' => $site,
        'layout' => $layout,
        'page' => $page,
        'widget' => $widget,
    ];
}

/**
 * @param  array{language: Language, site: Site, layout: Layout, page: Page}  $fixture
 */
function bindPublicFragmentFrontendContext(array $fixture): void
{
    Frontend::clearResolvedInstance(FrontendContextReader::class);
    app()->instance(
        FrontendContextReader::class,
        (new FrontendState)
            ->withSite($fixture['site'])
            ->withLanguage($fixture['language'])
            ->withPage($fixture['page'])
            ->withLayout($fixture['layout']),
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

function publicFragmentUrl(string $reference): string
{
    return route(
        'capell-layout-builder.fragments.show',
        ['reference' => $reference],
        absolute: false,
    );
}

function throwingPublicFragmentReferenceCodec(): PublicFragmentReferenceCodec
{
    return new class implements PublicFragmentReferenceCodec
    {
        public function encode(PublicFragmentReferenceData $reference): string
        {
            throw new LogicException('Encoding is not supported by this test codec.');
        }

        public function decode(string $token): PublicFragmentReferenceData
        {
            throw new RuntimeException('Codec container failure with internal details.');
        }
    };
}

/**
 * Re-encode a fixture reference with a mutated owner context, keeping its
 * content version valid so the render reaches the owner-context checks.
 *
 * @param  array{reference: string, language: Language, site: Site, layout: Layout, page: Page, widget: Widget}  $fixture
 * @param  array<string, int|string>  $overrides
 */
function publicFragmentReferenceWithOwnerContext(array $fixture, array $overrides): string
{
    $codec = resolve(PublicFragmentReferenceCodec::class);
    $decoded = $codec->decode($fixture['reference']);
    $ownerContext = [...$decoded->ownerContext, ...$overrides];

    return $codec->encode(new PublicFragmentReferenceData(
        owner: $decoded->owner,
        formatVersion: $decoded->formatVersion,
        pageableType: $decoded->pageableType,
        pageableId: $decoded->pageableId,
        siteId: $decoded->siteId,
        languageId: $decoded->languageId,
        contentVersion: ResolvePublicFragmentContentVersionAction::run(
            $fixture['page'],
            $fixture['site'],
            $fixture['language'],
            $fixture['layout'],
            $ownerContext,
        ),
        ownerContext: $ownerContext,
    ));
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
