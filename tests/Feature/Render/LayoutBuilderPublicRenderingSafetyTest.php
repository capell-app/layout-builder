<?php

declare(strict_types=1);

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\FrontendRuntime;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Contracts\FrontendResponseRenderer;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\Frontend\Http\Controllers\PageController;
use Capell\Frontend\Support\Render\FrontendResponseRendererRegistry;
use Capell\LayoutBuilder\Models\Element;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

it('does not force layout builder admin metadata into public responses', function (): void {
    $element = Element::factory()->create(['key' => 'hero']);
    $layout = Layout::factory()->create(['containers' => [
        'main' => [
            'elements' => [['element_key' => $element->key]],
            'meta' => [
                'colspan' => 12,
                'responsive' => ['mobile' => ['colspan' => 6]],
            ],
        ],
    ]]);
    $page = Page::factory()->withTranslations()->layout($layout)->create();
    $site = Site::factory()->create();
    $language = Language::factory()->create();
    $theme = new Theme;
    $theme->key = 'test-theme';

    app()->instance(FrontendContextReader::class, new class($page, $site, $language, $layout, $theme) implements FrontendContextReader
    {
        public function __construct(
            private readonly Pageable $page,
            private readonly Site $site,
            private readonly Language $language,
            private readonly Layout $layout,
            private readonly Theme $theme,
        ) {}

        public function site(): Site
        {
            return $this->site;
        }

        public function language(): Language
        {
            return $this->language;
        }

        public function page(): Pageable
        {
            return $this->page;
        }

        public function layout(): Layout
        {
            return $this->layout;
        }

        public function theme(): Theme
        {
            return $this->theme;
        }

        public function params(): array
        {
            return [];
        }

        public function slug(): ?string
        {
            return null;
        }

        public function isError(): bool
        {
            return false;
        }

        public function setFrontendData(string $key, mixed $value): self
        {
            return $this;
        }

        public function getFrontendData(?string $key = null): mixed
        {
            return null;
        }
    });

    $renderer = new class implements FrontendResponseRenderer
    {
        public function runtime(): FrontendRuntime
        {
            return FrontendRuntime::Blade;
        }

        public function render(FrontendRenderContextData $context): SymfonyResponse
        {
            expect($context->layout?->containers)->not->toBeEmpty();

            return response()->make(json_encode([
                'page' => $context->page?->getKey(),
            ], JSON_THROW_ON_ERROR));
        }
    };

    resolve(FrontendResponseRendererRegistry::class)->register($renderer);

    $html = resolve(PageController::class)()->getContent();

    expect($html)->not->toContain('layoutDiagnostics')
        ->and($html)->not->toContain('LayoutFragmentData')
        ->and($html)->not->toContain('signed editor')
        ->and($html)->not->toContain('pageable_type')
        ->and($html)->not->toContain('element_key')
        ->and($html)->not->toContain('responsive')
        ->and($html)->not->toContain('colspan');
});
