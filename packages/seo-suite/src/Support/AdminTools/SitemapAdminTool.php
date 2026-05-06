<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Support\AdminTools;

use Capell\Admin\Contracts\AdminTools\AdminToolItem;
use Illuminate\Support\Facades\Blade;

class SitemapAdminTool implements AdminToolItem
{
    public function render(): string
    {
        return Blade::render('<livewire:capell-seo-suite.tools.sitemap-tool />');
    }
}
