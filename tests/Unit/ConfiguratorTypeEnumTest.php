<?php

declare(strict_types=1);

use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;

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
