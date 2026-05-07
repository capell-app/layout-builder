<?php

declare(strict_types=1);

namespace Capell\ContentSections\Support;

use Capell\ContentSections\Contracts\SectionDefinitionProvider;
use Capell\ContentSections\Data\SectionDefinitionData;
use Capell\ContentSections\Enums\SectionConfiguratorEnum;
use Filament\Support\Icons\Heroicon;

class DefaultSectionDefinitionProvider implements SectionDefinitionProvider
{
    /**
     * @return iterable<SectionDefinitionData>
     */
    public function definitions(): iterable
    {
        return [
            $this->definition('content', SectionConfiguratorEnum::Default->value, Heroicon::OutlinedDocumentText),
            $this->definition('hero', SectionConfiguratorEnum::Hero->value, Heroicon::OutlinedSparkles),
            $this->definition('testimonial', SectionConfiguratorEnum::Testimonial->value, Heroicon::OutlinedChatBubbleBottomCenterText),
            $this->definition('accordion', SectionConfiguratorEnum::Accordion->value, Heroicon::OutlinedQueueList),
            $this->definition('call_to_action', SectionConfiguratorEnum::CallToAction->value, Heroicon::OutlinedCursorArrowRays),
            $this->definition('comparison', SectionConfiguratorEnum::Comparison->value, Heroicon::OutlinedArrowsRightLeft),
            $this->definition('counter', SectionConfiguratorEnum::Counter->value, Heroicon::OutlinedCalculator),
            $this->definition('divider', SectionConfiguratorEnum::Divider->value, Heroicon::OutlinedMinus),
            $this->definition('faq', SectionConfiguratorEnum::Faq->value, Heroicon::OutlinedQuestionMarkCircle),
            $this->definition('features', SectionConfiguratorEnum::Features->value, Heroicon::OutlinedSquares2x2),
            $this->definition('logos', SectionConfiguratorEnum::Logos->value, Heroicon::OutlinedBuildingOffice2),
            $this->definition('pricing', SectionConfiguratorEnum::Pricing->value, Heroicon::OutlinedCreditCard),
            $this->definition('stats', SectionConfiguratorEnum::Stats->value, Heroicon::OutlinedChartBar),
            $this->definition('table', SectionConfiguratorEnum::Table->value, Heroicon::OutlinedTableCells),
            $this->definition('tabs', SectionConfiguratorEnum::Tabs->value, Heroicon::OutlinedRectangleGroup),
            $this->definition('team', SectionConfiguratorEnum::Team->value, Heroicon::OutlinedUserGroup),
            $this->definition('timeline', SectionConfiguratorEnum::Timeline->value, Heroicon::OutlinedClock),
        ];
    }

    private function definition(string $key, string $configurator, string|Heroicon $icon): SectionDefinitionData
    {
        return new SectionDefinitionData(
            key: $key,
            label: __('capell-content-sections::section.' . $key . '.label'),
            description: __('capell-content-sections::section.' . $key . '.description'),
            icon: $icon,
            group: 'main',
            configurator: $configurator,
            component: 'capell-content-sections::section.blocks.' . str_replace('_', '-', $key),
            defaults: [],
        );
    }
}
