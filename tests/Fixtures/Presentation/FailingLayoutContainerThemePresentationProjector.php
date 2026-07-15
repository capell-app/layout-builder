<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\Presentation;

use Capell\LayoutBuilder\Contracts\LayoutContainerThemePresentationProjector;
use Capell\LayoutBuilder\Data\LayoutContainerThemePresentationData;
use RuntimeException;

final class FailingLayoutContainerThemePresentationProjector implements LayoutContainerThemePresentationProjector
{
    public function themeKey(): string
    {
        return 'failing-theme';
    }

    public function project(array $state): LayoutContainerThemePresentationData
    {
        throw new RuntimeException('Projection failed.');
    }
}
