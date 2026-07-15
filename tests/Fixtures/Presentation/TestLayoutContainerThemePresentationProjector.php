<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\Presentation;

use Capell\LayoutBuilder\Contracts\LayoutContainerThemePresentationProjector;
use Capell\LayoutBuilder\Data\LayoutContainerThemePresentationData;

final class TestLayoutContainerThemePresentationProjector implements LayoutContainerThemePresentationProjector
{
    public function themeKey(): string
    {
        return 'test-theme';
    }

    public function project(array $state): LayoutContainerThemePresentationData
    {
        $tone = $state['tone'] ?? null;

        return new TestLayoutContainerThemePresentationData(
            tone: is_string($tone) && in_array($tone, ['default', 'muted'], true) ? $tone : 'default',
        );
    }
}
