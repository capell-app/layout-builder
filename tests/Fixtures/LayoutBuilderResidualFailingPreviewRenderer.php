<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewRenderer;
use Override;
use RuntimeException;

final class LayoutBuilderResidualFailingPreviewRenderer extends LayoutPreviewRenderer
{
    #[Override]
    public function render(Layout $layout): string
    {
        throw new RuntimeException('Renderer failed with a deliberately long message for coverage.');
    }
}
