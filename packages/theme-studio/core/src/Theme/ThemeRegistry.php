<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Theme;

use Capell\ThemeStudio\Core\Contracts\SectionRenderer;
use Capell\ThemeStudio\Core\Contracts\ThemeRenderer;
use Capell\ThemeStudio\Core\Data\ThemeDefinitionData;
use Capell\ThemeStudio\Core\Exceptions\ThemeNotFoundException;

class ThemeRegistry
{
    /** @var array<string, ThemeDefinitionData> */
    private array $definitions = [];

    /** @var array<string, ThemeRenderer> */
    private array $themeRenderers = [];

    /** @var array<string, array<string, SectionRenderer>> */
    private array $sectionRenderers = [];

    /**
     * @param  array<int, SectionRenderer>  $sectionRenderers
     */
    public function register(
        ThemeDefinitionData $definition,
        ThemeRenderer $themeRenderer,
        array $sectionRenderers,
    ): void {
        $this->definitions[$definition->key] = $definition;
        $this->themeRenderers[$definition->key] = $themeRenderer;
        $this->sectionRenderers[$definition->key] = [];

        foreach ($sectionRenderers as $sectionRenderer) {
            $this->sectionRenderers[$definition->key][$sectionRenderer->sectionKey()] = $sectionRenderer;
        }
    }

    /**
     * @return array<string, ThemeDefinitionData>
     */
    public function definitions(): array
    {
        ksort($this->definitions);

        return $this->definitions;
    }

    public function definition(string $themeKey): ThemeDefinitionData
    {
        return $this->definitions[$themeKey] ?? throw ThemeNotFoundException::forKey($themeKey);
    }

    public function renderer(string $themeKey): ThemeRenderer
    {
        return $this->themeRenderers[$themeKey] ?? throw ThemeNotFoundException::forKey($themeKey);
    }

    public function sectionRenderer(string $themeKey, string $sectionKey): ?SectionRenderer
    {
        return $this->sectionRenderers[$themeKey][$sectionKey] ?? null;
    }

    public function has(string $themeKey): bool
    {
        return isset($this->definitions[$themeKey]);
    }

    public function reset(): void
    {
        $this->definitions = [];
        $this->themeRenderers = [];
        $this->sectionRenderers = [];
    }
}
