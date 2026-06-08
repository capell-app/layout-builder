<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Frontend\Contracts\PublicLayoutGraphBuilder;
use Capell\LayoutBuilder\Actions\BuildPublicLayoutGraphAction;
use Capell\LayoutBuilder\Data\PublicLayoutGraphData;

final class LayoutBuilderPublicLayoutGraphBuilder implements PublicLayoutGraphBuilder
{
    public function build(Layout $layout, Page $page, Language $language): PublicLayoutGraphData
    {
        return BuildPublicLayoutGraphAction::run($layout, $page, $language);
    }
}
