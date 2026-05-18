<?php

declare(strict_types=1);

use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\LayoutBuilder\Enums\BlockAssetConfiguratorEnum;
use Capell\LayoutBuilder\Enums\BlockConfiguratorEnum;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\LayoutBlockConfiguratorEnum;
use Capell\LayoutBuilder\Enums\LayoutContainerConfiguratorEnum;

it('owns layout configurator group metadata in the layout builder package', function (): void {
    $reflection = new ReflectionClass(ConfiguratorTypeEnum::class);

    expect($reflection->getFileName())->toContain('packages/layout-builder/src')
        ->and(ConfiguratorTypeEnum::LayoutContainer)->toBeInstanceOf(ConfiguratorTypeEnumInterface::class)
        ->and(ConfiguratorTypeEnum::fromName('Block'))->toBe(ConfiguratorTypeEnum::Block)
        ->and(ConfiguratorTypeEnum::BlockAsset->getName())->toBe('BlockAsset')
        ->and(array_keys(ConfiguratorTypeEnum::getAllConfigurators()))->toBe([
            'LayoutContainers',
            'LayoutBlocks',
            'Blocks',
            'BlockAssets',
        ]);
});

it('uses package-owned configurator enum lists instead of core layout builder enums', function (): void {
    $reflection = new ReflectionClass(ConfiguratorTypeEnum::class);
    $source = file_get_contents((string) $reflection->getFileName());

    expect($source)->not->toContain('Capell\\Core\\LayoutBuilder\\Enums')
        ->and(ConfiguratorTypeEnum::LayoutContainer->getConfigurators())->toBe(LayoutContainerConfiguratorEnum::cases())
        ->and(ConfiguratorTypeEnum::LayoutBlock->getConfigurators())->toBe(LayoutBlockConfiguratorEnum::cases())
        ->and(ConfiguratorTypeEnum::Block->getConfigurators())->toBe(BlockConfiguratorEnum::cases())
        ->and(ConfiguratorTypeEnum::BlockAsset->getConfigurators())->toBe(BlockAssetConfiguratorEnum::cases());
});

it('advertises package namespace configurator classes', function (): void {
    $configuratorClasses = collect(ConfiguratorTypeEnum::getAllConfigurators())
        ->flatten()
        ->map(fn (BackedEnum $configurator): string => $configurator->value)
        ->values();

    expect($configuratorClasses)
        ->each->toStartWith('Capell\\LayoutBuilder\\Filament\\Configurators\\');
});
