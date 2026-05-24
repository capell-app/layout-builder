<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\TranslationFactory;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Actions\Fragments\RenderPublicFragmentAction;
use Capell\LayoutBuilder\Contracts\PublicBlockPayloadContributor;
use Capell\LayoutBuilder\Models\Block;
use Capell\LayoutBuilder\Support\Livewire\OpaqueBlockReference;

it('renders a valid encrypted public block fragment reference', function (): void {
    [$reference] = publicFragmentFixture('<section class="fragment-card">Public fragment</section>');

    expect(RenderPublicFragmentAction::run($reference))->toBe('<section class="fragment-card">Public fragment</section>');
});

it('exposes the public fragment route with fragment specific cache headers', function (): void {
    [$reference] = publicFragmentFixture('<section>Route fragment</section>');

    $this->get('/_capell/fragments/' . rawurlencode($reference))
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=300, public, stale-while-revalidate=60')
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertSee('Route fragment', false);
});

it('returns a generic 404 for invalid encrypted public block references', function (): void {
    $this->get('/_capell/fragments/not-a-valid-token')
        ->assertNotFound()
        ->assertDontSee('decrypt', false)
        ->assertDontSee('token', false);

    expect(RenderPublicFragmentAction::run('not-a-valid-token'))->toBeNull();
});

it('does not render a replayed reference for another site and page', function (): void {
    [, $referenceData] = publicFragmentFixture('<section>Original fragment</section>');
    $otherLanguage = Language::factory()->create();
    $otherSite = Site::factory()->create(['language_id' => $otherLanguage->getKey()]);
    $otherLayout = Layout::factory()->site($otherSite)->create([
        'containers' => [
            'main' => ['blocks' => []],
        ],
    ]);
    $otherPage = Page::factory()->site($otherSite)->layout($otherLayout)->withTranslations($otherLanguage)->create();

    $replayedReference = OpaqueBlockReference::encode([
        ...$referenceData,
        'page_id' => $otherPage->getKey(),
        'page_type' => $otherPage->getMorphClass(),
    ]);

    expect(RenderPublicFragmentAction::run($replayedReference))->toBeNull();
});

it('blocks unsafe authoring surface html in public fragments', function (): void {
    [$reference] = publicFragmentFixture('<section data-capell-authoring="true">Unsafe fragment</section>');

    expect(RenderPublicFragmentAction::run($reference))->toBeNull();
});

/**
 * @return array{string, array<string, mixed>}
 */
function publicFragmentFixture(string $html): array
{
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->getKey()]);
    $block = Block::factory()->create(['key' => 'public-fragment-block']);

    TranslationFactory::new()
        ->translatable($block)
        ->language($language)
        ->create([
            'title' => 'Public fragment block',
            'content' => '<p>Base content</p>',
        ]);

    $layout = Layout::factory()->site($site)->create([
        'blocks' => [$block->key],
        'containers' => [
            'main' => ['blocks' => [['block_key' => $block->key, 'occurrence' => 1]]],
        ],
    ]);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    app()->singleton('test.public-fragment-payload-contributor', fn (): PublicBlockPayloadContributor => new class($html) implements PublicBlockPayloadContributor
    {
        public function __construct(private string $html) {}

        public function priority(): int
        {
            return 100;
        }

        public function data(Block $block, Page $page, Language $language, string $containerKey, int $occurrence): array
        {
            return [];
        }

        public function html(Block $block, Page $page, Language $language, string $containerKey, int $occurrence): string
        {
            return $this->html;
        }
    });
    app()->tag('test.public-fragment-payload-contributor', PublicBlockPayloadContributor::TAG);

    $referenceData = [
        'site_id' => $site->getKey(),
        'page_id' => $page->getKey(),
        'page_type' => $page->getMorphClass(),
        'layout_id' => $layout->getKey(),
        'language_id' => $language->getKey(),
        'container_key' => 'main',
        'block_key' => $block->key,
        'occurrence' => 1,
        'block_data' => [],
    ];

    return [OpaqueBlockReference::encode($referenceData), $referenceData];
}
