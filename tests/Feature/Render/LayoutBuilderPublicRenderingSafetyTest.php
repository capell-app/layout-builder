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
use Capell\LayoutBuilder\Models\Block;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

it('does not force layout builder admin metadata into public responses', function (): void {
    $block = Block::factory()->create(['key' => 'hero']);
    $layout = Layout::factory()->create(['containers' => [
        'main' => [
            'blocks' => [['block_key' => $block->key]],
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

    app()->instance(FrontendContextReader::class, new readonly class($page, $site, $language, $layout, $theme) implements FrontendContextReader
    {
        public function __construct(
            private Pageable $page,
            private Site $site,
            private Language $language,
            private Layout $layout,
            private Theme $theme,
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
        ->and($html)->not->toContain('block_key')
        ->and($html)->not->toContain('responsive')
        ->and($html)->not->toContain('colspan');
});
