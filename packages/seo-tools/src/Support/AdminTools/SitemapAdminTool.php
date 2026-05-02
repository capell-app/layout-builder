<?php

declare(strict_types=1);

namespace Capell\SeoTools\Support\AdminTools;

use Capell\Admin\Contracts\AdminTools\AdminToolItem;
use Illuminate\Support\Facades\Blade;

class SitemapAdminTool implements AdminToolItem
{
    public function render(): string
    {
        return Blade::render('<livewire:capell-seo-tools.tools.sitemap-tool />');
    }
}
