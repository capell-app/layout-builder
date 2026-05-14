<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Filament\Concerns\HasConfigurator;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\SchemaExtenderEnum;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

abstract class AbstractWidgetAssetConfigurator implements ConfiguratorInterface
{
    use HasConfigurator;

    protected static ConfiguratorTypeEnumInterface $configuratorType = ConfiguratorTypeEnum::WidgetAsset;

    abstract protected function getAssetSchema(Schema $configurator): array;

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::WidgetAsset->value);
    }

    public function make(Schema $configurator): array
    {
        return [
            Grid::make()
                ->relationship('asset')
                ->columnSpanFull()
                ->schema($this->getAssetSchema($configurator)),
        ];
    }
}
