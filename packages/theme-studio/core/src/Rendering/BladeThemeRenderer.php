<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Rendering;

use Capell\ThemeStudio\Core\Contracts\SectionRenderer;
use Capell\ThemeStudio\Core\Contracts\ThemeRenderer;
use Capell\ThemeStudio\Core\Contracts\ThemeSection;
use Capell\ThemeStudio\Core\Data\ThemePageData;
use Capell\ThemeStudio\Core\Exceptions\SectionRendererNotFoundException;
use Throwable;

class BladeThemeRenderer implements ThemeRenderer
{
    /**
     * @param  array<string, SectionRenderer>  $sectionRenderers
     */
    public function __construct(
        private readonly string $themeKey,
        private readonly string $layoutView,
        private readonly array $sectionRenderers,
    ) {}

    public function themeKey(): string
    {
        return $this->themeKey;
    }

    public function render(ThemePageData $page): string
    {
        $html = [];

        foreach ($page->allSections() as $section) {
            $html[] = $this->renderSection($section);
        }

        $content = implode("\n", $html);

        if (function_exists('view')) {
            try {
                return view($this->layoutView, [
                    'brand' => $page->brand,
                    'content' => $content,
                    'page' => $page,
                    'themeKey' => $this->themeKey,
                ])->render();
            } catch (Throwable) {
                return $content;
            }
        }

        return $content;
    }

    private function renderSection(ThemeSection $section): string
    {
        $renderer = $this->sectionRenderers[$section->key()] ?? null;

        if ($renderer === null && $section->fallbackKey() !== null) {
            $renderer = $this->sectionRenderers[$section->fallbackKey()] ?? null;
        }

        if ($renderer === null) {
            throw SectionRendererNotFoundException::forSection($this->themeKey, $section->key());
        }

        return $renderer->render($section);
    }
}
