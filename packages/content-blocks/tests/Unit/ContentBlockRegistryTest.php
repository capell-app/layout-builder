<?php

declare(strict_types=1);

use Capell\ContentBlocks\Actions\RegisterDefaultContentBlocksAction;
use Capell\ContentBlocks\Data\ContentBlockDefinitionData;
use Capell\ContentBlocks\Enums\ContentBlockConfiguratorEnum;
use Capell\ContentBlocks\Support\ContentBlockRegistry;
use Filament\Support\Icons\Heroicon;

it('registers the main content blocks', function (): void {
    $registry = new ContentBlockRegistry;

    RegisterDefaultContentBlocksAction::run($registry);

    expect(array_keys($registry->all()))->toContain(
        'accordion',
        'call_to_action',
        'comparison',
        'counter',
        'divider',
        'faq',
        'features',
        'logos',
        'pricing',
        'stats',
        'table',
        'tabs',
        'team',
        'timeline',
    );
});

it('guards against duplicate block keys', function (): void {
    $registry = new ContentBlockRegistry;
    $definition = new ContentBlockDefinitionData(
        key: 'accordion',
        label: 'Accordion',
        description: 'Accordion panels.',
        icon: Heroicon::OutlinedQueueList,
        group: 'main',
        configurator: ContentBlockConfiguratorEnum::Accordion->value,
        component: 'capell-content-blocks::content-block.blocks.accordion',
    );

    $registry->register($definition);
    $registry->register($definition);
})->throws(InvalidArgumentException::class);
