<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Contracts;

interface SectionRenderer
{
    public function themeKey(): string;

    public function sectionKey(): string;

    public function render(ThemeSection $section): string;
}
