<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions;

use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionStateUpcaster;
use Override;

final class UnresolvableStateUpcaster implements WidgetExtensionStateUpcaster
{
    public function __construct(
        private readonly MissingStateUpcasterDependency $dependency,
    ) {}

    #[Override]
    public function upcast(array $state, int $fromVersion, int $toVersion): array
    {
        return $state;
    }

    public function dependency(): MissingStateUpcasterDependency
    {
        return $this->dependency;
    }
}
