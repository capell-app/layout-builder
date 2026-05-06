<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Support;

use Capell\BlockLibrary\Contracts\ContentBlockDefinitionProvider;
use Capell\BlockLibrary\Data\ContentBlockDefinitionData;
use Capell\BlockLibrary\Enums\ContentBlockConfiguratorEnum;
use Filament\Support\Icons\Heroicon;

class DefaultContentBlockDefinitionProvider implements ContentBlockDefinitionProvider
{
    /**
     * @return iterable<ContentBlockDefinitionData>
     */
    public function definitions(): iterable
    {
        return [
            $this->definition('content', ContentBlockConfiguratorEnum::Default->value, Heroicon::OutlinedDocumentText),
            $this->definition('hero', ContentBlockConfiguratorEnum::Hero->value, Heroicon::OutlinedSparkles),
            $this->definition('testimonial', ContentBlockConfiguratorEnum::Testimonial->value, Heroicon::OutlinedChatBubbleBottomCenterText),
            $this->definition('accordion', ContentBlockConfiguratorEnum::Accordion->value, Heroicon::OutlinedQueueList),
            $this->definition('call_to_action', ContentBlockConfiguratorEnum::CallToAction->value, Heroicon::OutlinedCursorArrowRays),
            $this->definition('comparison', ContentBlockConfiguratorEnum::Comparison->value, Heroicon::OutlinedArrowsRightLeft),
            $this->definition('counter', ContentBlockConfiguratorEnum::Counter->value, Heroicon::OutlinedCalculator),
            $this->definition('divider', ContentBlockConfiguratorEnum::Divider->value, Heroicon::OutlinedMinus),
            $this->definition('faq', ContentBlockConfiguratorEnum::Faq->value, Heroicon::OutlinedQuestionMarkCircle),
            $this->definition('features', ContentBlockConfiguratorEnum::Features->value, Heroicon::OutlinedSquares2x2),
            $this->definition('logos', ContentBlockConfiguratorEnum::Logos->value, Heroicon::OutlinedBuildingOffice2),
            $this->definition('pricing', ContentBlockConfiguratorEnum::Pricing->value, Heroicon::OutlinedCreditCard),
            $this->definition('stats', ContentBlockConfiguratorEnum::Stats->value, Heroicon::OutlinedChartBar),
            $this->definition('table', ContentBlockConfiguratorEnum::Table->value, Heroicon::OutlinedTableCells),
            $this->definition('tabs', ContentBlockConfiguratorEnum::Tabs->value, Heroicon::OutlinedRectangleGroup),
            $this->definition('team', ContentBlockConfiguratorEnum::Team->value, Heroicon::OutlinedUserGroup),
            $this->definition('timeline', ContentBlockConfiguratorEnum::Timeline->value, Heroicon::OutlinedClock),
        ];
    }

    private function definition(string $key, string $configurator, string|Heroicon $icon): ContentBlockDefinitionData
    {
        return new ContentBlockDefinitionData(
            key: $key,
            label: __('capell-block-library::block.' . $key . '.label'),
            description: __('capell-block-library::block.' . $key . '.description'),
            icon: $icon,
            group: 'main',
            configurator: $configurator,
            component: 'capell-block-library::content-block.blocks.' . str_replace('_', '-', $key),
            defaults: [],
        );
    }
}
