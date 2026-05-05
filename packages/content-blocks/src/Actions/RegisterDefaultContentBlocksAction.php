<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Actions;

use Capell\ContentBlocks\Data\ContentBlockDefinitionData;
use Capell\ContentBlocks\Enums\ContentBlockConfiguratorEnum;
use Capell\ContentBlocks\Support\ContentBlockRegistry;
use Filament\Support\Icons\Heroicon;
use Lorisleiva\Actions\Concerns\AsObject;

class RegisterDefaultContentBlocksAction
{
    use AsObject;

    public function handle(ContentBlockRegistry $registry): void
    {
        foreach ($this->definitions() as $definition) {
            $registry->register($definition);
        }
    }

    /**
     * @return array<int, ContentBlockDefinitionData>
     */
    private function definitions(): array
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
            label: __('capell-content-blocks::block.' . $key . '.label'),
            description: __('capell-content-blocks::block.' . $key . '.description'),
            icon: $icon,
            group: 'main',
            configurator: $configurator,
            component: 'capell-content-blocks::content-block.blocks.' . str_replace('_', '-', $key),
            defaults: [],
        );
    }
}
