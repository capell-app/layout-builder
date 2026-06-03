<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;

final class LayoutBuilderResidualFrontendContextForLoadedLayout
{
    public function __construct(
        private readonly Layout $layout,
        private readonly Language $language,
        private readonly Page $page,
    ) {}

    public function layout(): Layout
    {
        return $this->layout;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function page(): Page
    {
        return $this->page;
    }
}
