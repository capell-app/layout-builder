<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\Presentation;

use Capell\LayoutBuilder\Data\LayoutContainerThemePresentationData;

final class TestLayoutContainerThemePresentationData extends LayoutContainerThemePresentationData
{
    public function __construct(public readonly string $tone) {}

    public function classes(): array
    {
        return $this->tone === 'muted' ? ['test-container-tone-muted'] : [];
    }
}
