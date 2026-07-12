<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewRenderer;
use Override;

final class LayoutBuilderResidualSuccessfulPreviewRenderer extends LayoutPreviewRenderer
{
    #[Override]
    public function render(Layout $layout): string
    {
        return 'png:' . $layout->getKey();
    }
}
