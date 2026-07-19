<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Frontend\Contracts\FrontendComponentContributor;
use Capell\Frontend\Data\FrontendComponentContributionData;
use Capell\Frontend\Enums\FrontendComponentTarget;
use Capell\LayoutBuilder\Enums\LayoutWidgetTarget;
use Capell\LayoutBuilder\Support\LayoutWidgets\LayoutWidgetRegistry;

final readonly class LayoutBuilderFrontendComponentContributor implements FrontendComponentContributor
{
    public function __construct(
        private LayoutWidgetRegistry $widgets,
    ) {}

    /** @return list<FrontendComponentContributionData> */
    public function components(): array
    {
        return [
            ...$this->componentsFor(LayoutWidgetTarget::FrontendBlade, FrontendComponentTarget::Blade),
            ...$this->componentsFor(LayoutWidgetTarget::FrontendLivewire, FrontendComponentTarget::Livewire),
        ];
    }

    /** @return list<FrontendComponentContributionData> */
    private function componentsFor(
        LayoutWidgetTarget $layoutTarget,
        FrontendComponentTarget $frontendTarget,
    ): array {
        $components = [];

        foreach ($this->widgets->allForTarget($layoutTarget) as $name => $component) {
            $components[] = new FrontendComponentContributionData(
                name: $name,
                component: $component,
                target: $frontendTarget,
            );
        }

        return $components;
    }
}
