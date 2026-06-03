<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\TranslationFactory;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Actions\Fragments\RenderPublicFragmentAction;
use Capell\LayoutBuilder\Contracts\PublicWidgetPayloadContributor;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Livewire\OpaqueWidgetReference;

it('renders a valid encrypted public widget fragment reference', function (): void {
    [$reference] = publicFragmentFixture('<section class="fragment-card">Public fragment</section>');

    expect(RenderPublicFragmentAction::run($reference))->toBe('<section class="fragment-card">Public fragment</section>');
});

it('exposes the public fragment route with fragment specific cache headers', function (): void {
    [$reference] = publicFragmentFixture('<section>Route fragment</section>');

    $this->get('/_fragments/' . rawurlencode($reference))
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=300, public, stale-while-revalidate=60')
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertSee('Route fragment', false);
});

it('returns a generic 404 for invalid encrypted public widget references', function (): void {
    $this->get('/_fragments/not-a-valid-token')
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
            'main' => ['widgets' => []],
        ],
    ]);
    $otherPage = Page::factory()->site($otherSite)->layout($otherLayout)->withTranslations($otherLanguage)->create();

    $replayedReference = OpaqueWidgetReference::encode([
        ...$referenceData,
        'page_id' => $otherPage->getKey(),
        'page_type' => $otherPage->getMorphClass(),
    ]);

    expect(RenderPublicFragmentAction::run($replayedReference))->toBeNull();
});

it('widgets unsafe authoring surface html in public fragments', function (): void {
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
    $widget = Widget::factory()->create(['key' => 'public-fragment-widget']);

    TranslationFactory::new()
        ->translatable($widget)
        ->language($language)
        ->create([
            'title' => 'Public fragment widget',
            'content' => '<p>Base content</p>',
        ]);

    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]],
        ],
    ]);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    app()->singleton('test.public-fragment-payload-contributor', fn (): PublicWidgetPayloadContributor => new class($html) implements PublicWidgetPayloadContributor
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
    app()->tag('test.public-fragment-payload-contributor', PublicWidgetPayloadContributor::TAG);

    $referenceData = [
        'site_id' => $site->getKey(),
        'page_id' => $page->getKey(),
        'page_type' => $page->getMorphClass(),
        'layout_id' => $layout->getKey(),
        'language_id' => $language->getKey(),
        'container_key' => 'main',
        'widget_key' => $widget->key,
        'occurrence' => 1,
        'widget_data' => [],
    ];

    return [OpaqueWidgetReference::encode($referenceData), $referenceData];
}
