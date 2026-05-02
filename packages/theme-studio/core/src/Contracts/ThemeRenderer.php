<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Contracts;

use Capell\ThemeStudio\Core\Data\ThemePageData;

interface ThemeRenderer
{
    public function themeKey(): string;

    public function render(ThemePageData $page): string;
}
