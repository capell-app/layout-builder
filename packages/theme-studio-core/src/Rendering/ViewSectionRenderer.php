<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Rendering;

use Capell\ThemeStudio\Core\Contracts\SectionRenderer;
use Capell\ThemeStudio\Core\Contracts\ThemeSection;
use Throwable;

class ViewSectionRenderer implements SectionRenderer
{
    public function __construct(
        private readonly string $themeKey,
        private readonly string $sectionKey,
        private readonly string $view,
        private readonly bool $failLoudly = false,
    ) {}

    public function themeKey(): string
    {
        return $this->themeKey;
    }

    public function sectionKey(): string
    {
        return $this->sectionKey;
    }

    public function render(ThemeSection $section): string
    {
        if (function_exists('view')) {
            try {
                return view($this->view, $section->toViewData())->render();
            } catch (Throwable $throwable) {
                throw_if($this->failLoudly, $throwable);

                return $this->fallbackHtml($section);
            }
        }

        return $this->fallbackHtml($section);
    }

    private function fallbackHtml(ThemeSection $section): string
    {
        return '<section data-theme="' . $this->escape($this->themeKey) . '" data-section="' . $this->escape($section->key()) . '"></section>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
