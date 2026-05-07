<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Contracts;

use Capell\ThemeStudio\Core\Data\ThemePageData;

interface ThemePageAdapter
{
    public function currentPage(): ThemePageData;
}
