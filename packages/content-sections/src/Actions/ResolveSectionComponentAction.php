<?php

declare(strict_types=1);

namespace Capell\ContentSections\Actions;

use Capell\ContentSections\Support\SectionRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class ResolveSectionComponentAction
{
    use AsObject;

    public function handle(?string $configurator, string $fallbackComponent): string
    {
        if (blank($configurator)) {
            return $fallbackComponent;
        }

        $definition = resolve(SectionRegistry::class)->getByConfigurator($configurator);

        return $definition?->component ?? $fallbackComponent;
    }
}
