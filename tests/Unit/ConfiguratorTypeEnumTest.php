<?php

declare(strict_types=1);

use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\ElementAssetConfiguratorEnum;
use Capell\LayoutBuilder\Enums\ElementConfiguratorEnum;
use Capell\LayoutBuilder\Enums\LayoutContainerConfiguratorEnum;
use Capell\LayoutBuilder\Enums\LayoutElementConfiguratorEnum;

it('owns layout configurator group metadata in the layout builder package', function (): void {
    $reflection = new ReflectionClass(ConfiguratorTypeEnum::class);

    expect($reflection->getFileName())->toContain('packages/layout-builder/src')
        ->and(ConfiguratorTypeEnum::LayoutContainer)->toBeInstanceOf(ConfiguratorTypeEnumInterface::class)
        ->and(ConfiguratorTypeEnum::fromName('Element'))->toBe(ConfiguratorTypeEnum::Element)
        ->and(ConfiguratorTypeEnum::ElementAsset->getName())->toBe('ElementAsset')
        ->and(array_keys(ConfiguratorTypeEnum::getAllConfigurators()))->toBe([
            'LayoutContainers',
            'LayoutElements',
            'Elements',
            'ElementAssets',
        ]);
});

it('uses package-owned configurator enum lists instead of core layout builder enums', function (): void {
    $reflection = new ReflectionClass(ConfiguratorTypeEnum::class);
    $source = file_get_contents((string) $reflection->getFileName());

    expect($source)->not->toContain('Capell\\Core\\LayoutBuilder\\Enums')
        ->and(ConfiguratorTypeEnum::LayoutContainer->getConfigurators())->toBe(LayoutContainerConfiguratorEnum::cases())
        ->and(ConfiguratorTypeEnum::LayoutElement->getConfigurators())->toBe(LayoutElementConfiguratorEnum::cases())
        ->and(ConfiguratorTypeEnum::Element->getConfigurators())->toBe(ElementConfiguratorEnum::cases())
        ->and(ConfiguratorTypeEnum::ElementAsset->getConfigurators())->toBe(ElementAssetConfiguratorEnum::cases());
});

it('advertises package namespace configurator classes', function (): void {
    $configuratorClasses = collect(ConfiguratorTypeEnum::getAllConfigurators())
        ->flatten()
        ->map(fn (BackedEnum $configurator): string => $configurator->value)
        ->values();

    expect($configuratorClasses)
        ->each->toStartWith('Capell\\LayoutBuilder\\Filament\\Configurators\\');
});
