<?php

declare(strict_types=1);

use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\LayoutContainerConfiguratorEnum;
use Capell\LayoutBuilder\Enums\LayoutWidgetConfiguratorEnum;
use Capell\LayoutBuilder\Enums\WidgetAssetConfiguratorEnum;
use Capell\LayoutBuilder\Enums\WidgetConfiguratorEnum;

it('owns layout configurator group metadata in the layout builder package', function (): void {
    $reflection = new ReflectionClass(ConfiguratorTypeEnum::class);

    expect($reflection->getFileName())->toContain('packages/layout-builder/src')
        ->and(ConfiguratorTypeEnum::LayoutContainer)->toBeInstanceOf(ConfiguratorTypeEnumInterface::class)
        ->and(ConfiguratorTypeEnum::fromName('Widget'))->toBe(ConfiguratorTypeEnum::Widget)
        ->and(ConfiguratorTypeEnum::WidgetAsset->getName())->toBe('WidgetAsset')
        ->and(array_keys(ConfiguratorTypeEnum::getAllConfigurators()))->toBe([
            'LayoutContainers',
            'LayoutWidgets',
            'Widgets',
            'WidgetAssets',
        ]);
});

it('uses package-owned configurator enum lists instead of core layout builder enums', function (): void {
    $reflection = new ReflectionClass(ConfiguratorTypeEnum::class);
    $source = file_get_contents((string) $reflection->getFileName());

    expect($source)->not->toContain('Capell\\Core\\LayoutBuilder\\Enums')
        ->and(ConfiguratorTypeEnum::LayoutContainer->getConfigurators())->toBe(LayoutContainerConfiguratorEnum::cases())
        ->and(ConfiguratorTypeEnum::LayoutWidget->getConfigurators())->toBe(LayoutWidgetConfiguratorEnum::cases())
        ->and(ConfiguratorTypeEnum::Widget->getConfigurators())->toBe(WidgetConfiguratorEnum::cases())
        ->and(ConfiguratorTypeEnum::WidgetAsset->getConfigurators())->toBe(WidgetAssetConfiguratorEnum::cases());
});

it('advertises package namespace configurator classes', function (): void {
    $configuratorClasses = collect(ConfiguratorTypeEnum::getAllConfigurators())
        ->flatten()
        ->map(fn (BackedEnum $configurator): string => $configurator->value)
        ->values();

    expect($configuratorClasses)
        ->each->toStartWith('Capell\\LayoutBuilder\\Filament\\Configurators\\');
});
