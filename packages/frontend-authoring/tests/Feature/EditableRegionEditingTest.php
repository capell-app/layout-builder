<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Translation;
use Capell\Frontend\Contracts\AdminAccessCheckerInterface;
use Capell\FrontendAuthoring\Actions\ClearAffectedCachedUrlsAction;
use Capell\FrontendAuthoring\Actions\CollectAffectedCachedUrlsAction;
use Capell\FrontendAuthoring\Actions\UpdateEditableRegionAction;
use Capell\FrontendAuthoring\Data\EditableRegionPayloadData;
use Capell\FrontendAuthoring\Http\Controllers\EditRegionController;
use Capell\FrontendAuthoring\Support\EditableRegionSigner;
use Capell\HtmlCache\Models\CachedModelUrl;
use Capell\HtmlCache\Support\Cache\HtmlCachePathResolver;
use Capell\Tests\Fixtures\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    Config::set('capell-frontend-authoring.enabled', true);
    Config::set('capell-admin.auto_refresh_cache', false);
});

function bindEditableRegionAdminAccess(bool $isAdmin): void
{
    app()->instance(AdminAccessCheckerInterface::class, new class($isAdmin) implements AdminAccessCheckerInterface
    {
        public function __construct(private readonly bool $isAdmin) {}

        public function isAdmin(Authenticatable $user): bool
        {
            return $this->isAdmin;
        }
    });
}

function createEditableRegionTranslation(array $attributes = []): Translation
{
    $page = Page::factory()->create();

    return Translation::factory()
        ->translatable($page)
        ->create([
            'title' => 'Original title',
            'content' => '<p>Original content</p>',
            'meta' => ['seo' => ['description' => 'Original description']],
            ...$attributes,
        ]);
}

function editableRegionPayload(Translation $translation, string $field = 'title'): EditableRegionPayloadData
{
    return new EditableRegionPayloadData(
        model: Translation::class,
        recordKey: (int) $translation->getKey(),
        field: $field,
        label: 'Editable field',
        type: 'text',
        selector: '[data-editable]',
        currentUrl: 'https://example.test/current',
    );
}

it('collects affected cached urls for the edited model record', function (): void {
    $translation = createEditableRegionTranslation();
    $otherTranslation = createEditableRegionTranslation();

    CachedModelUrl::query()->create([
        'url' => 'https://example.test/current',
        'url_hash' => CachedModelUrl::hashUrl('https://example.test/current'),
        'path' => '/current',
        'cacheable_type' => $translation->getMorphClass(),
        'cacheable_id' => $translation->getKey(),
    ]);
    CachedModelUrl::query()->create([
        'url' => 'https://example.test/duplicate',
        'url_hash' => CachedModelUrl::hashUrl('https://example.test/duplicate'),
        'path' => '/duplicate',
        'cacheable_type' => $translation->getMorphClass(),
        'cacheable_id' => $translation->getKey(),
    ]);
    CachedModelUrl::query()->create([
        'url' => 'https://example.test/other',
        'url_hash' => CachedModelUrl::hashUrl('https://example.test/other'),
        'path' => '/other',
        'cacheable_type' => $otherTranslation->getMorphClass(),
        'cacheable_id' => $otherTranslation->getKey(),
    ]);

    expect(CollectAffectedCachedUrlsAction::run($translation))->toBe([
        'https://example.test/current',
        'https://example.test/duplicate',
    ]);
});

it('clears affected cached urls and removes the edited model from the cache index', function (): void {
    Storage::fake('page_cache');

    $translation = createEditableRegionTranslation();
    $language = Language::factory()->create();
    $site = Site::factory()->hasSiteDomains()->create();
    $siteDomain = SiteDomain::factory()
        ->for($site)
        ->for($language)
        ->create([
            'scheme' => 'https',
            'domain' => 'example.test',
            'path' => '/',
            'status' => true,
        ]);
    $url = 'https://example.test/edited';
    $cachePath = resolve(HtmlCachePathResolver::class)->pathForUrl('/edited', $siteDomain);

    Storage::disk('page_cache')->put($cachePath, 'cached html');
    CachedModelUrl::query()->create([
        'url' => $url,
        'url_hash' => CachedModelUrl::hashUrl($url),
        'path' => '/edited',
        'site_domain_id' => $siteDomain->getKey(),
        'cacheable_type' => $translation->getMorphClass(),
        'cacheable_id' => $translation->getKey(),
    ]);
    CachedModelUrl::query()->create([
        'url' => $url,
        'url_hash' => CachedModelUrl::hashUrl($url),
        'path' => '/edited',
        'site_domain_id' => $siteDomain->getKey(),
        'cacheable_type' => Page::factory()->create()->getMorphClass(),
        'cacheable_id' => 123,
    ]);
    CachedModelUrl::query()->create([
        'url' => 'https://missing.test/edited',
        'url_hash' => CachedModelUrl::hashUrl('https://missing.test/edited'),
        'path' => '/edited',
        'cacheable_type' => $translation->getMorphClass(),
        'cacheable_id' => $translation->getKey(),
    ]);

    $cleared = ClearAffectedCachedUrlsAction::run(
        $translation,
        [$url, 'https://missing.test/edited'],
        'https://example.test/other',
    );

    expect($cleared)->toBe(1)
        ->and(Storage::disk('page_cache')->exists($cachePath))->toBeFalse()
        ->and(CachedModelUrl::query()->where('url', $url)->exists())->toBeFalse()
        ->and(CachedModelUrl::query()->where('url', 'https://missing.test/edited')->exists())->toBeFalse();
});

it('updates allowed editable region fields and rejects unknown fields', function (): void {
    $translation = createEditableRegionTranslation();

    $titleResult = UpdateEditableRegionAction::run(editableRegionPayload($translation, 'title'), 'Updated title');
    $metaResult = UpdateEditableRegionAction::run(editableRegionPayload($translation, 'meta.seo.description'), 'Updated description');

    $translation->refresh();

    expect($titleResult)->toBe(['cleared' => 0, 'urls' => []])
        ->and($metaResult)->toBe(['cleared' => 0, 'urls' => []])
        ->and($translation->title)->toBe('Updated title')
        ->and($translation->meta)->toHaveKey('seo.description', 'Updated description');

    expect(fn (): array => UpdateEditableRegionAction::run(editableRegionPayload($translation, 'admin.hidden'), 'Nope'))
        ->toThrow(HttpException::class);
});

it('encodes signed region payloads and rejects tampered payloads', function (): void {
    $translation = createEditableRegionTranslation();
    $signer = resolve(EditableRegionSigner::class);
    $payload = editableRegionPayload($translation, 'content');
    $encodedPayload = $signer->encode($payload);

    expect($signer->decode($encodedPayload)->toArray())->toBe($payload->toArray());

    $decodedJson = base64_decode(strtr($encodedPayload, '-_', '+/'), true);
    expect($decodedJson)->toBeString();

    $decodedPayload = json_decode((string) $decodedJson, associative: true, flags: JSON_THROW_ON_ERROR);
    $decodedPayload['data']['field'] = 'title';
    $tamperedPayload = rtrim(strtr(base64_encode(json_encode($decodedPayload, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');

    expect(fn (): EditableRegionPayloadData => $signer->decode($tamperedPayload))
        ->toThrow(HttpException::class);
});

it('protects the edit region route with authentication admin access and signed urls', function (): void {
    $translation = createEditableRegionTranslation();
    $signer = resolve(EditableRegionSigner::class);
    $signedUrl = $signer->signedEditUrl(editableRegionPayload($translation));

    getJson($signedUrl)->assertUnauthorized();

    $user = User::factory()->create();
    actingAs($user);

    bindEditableRegionAdminAccess(false);
    get($signedUrl)->assertForbidden();

    bindEditableRegionAdminAccess(true);

    $request = Request::create('/authoring/regions/' . $signer->encode(editableRegionPayload($translation)));
    $request->setUserResolver(fn (): User => $user);

    $view = resolve(EditRegionController::class)->__invoke($request, 'encoded-payload');

    expect($view->name())->toBe('capell::editor.region')
        ->and($view->getData())->toHaveKey('payload', 'encoded-payload');

    $tamperedUrl = str_replace('signature=', 'signature=invalid', $signedUrl);
    getJson($tamperedUrl)->assertForbidden();
});
