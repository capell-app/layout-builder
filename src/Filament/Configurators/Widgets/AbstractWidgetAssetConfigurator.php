<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Filament\Concerns\HasConfigurator;
use Capell\LayoutBuilder\Contracts\Extenders\WidgetAssetSchemaExtender;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\SchemaExtenderEnum;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

abstract class AbstractWidgetAssetConfigurator implements ConfiguratorInterface
{
    use HasConfigurator;

    protected static ConfiguratorTypeEnumInterface $configuratorType = ConfiguratorTypeEnum::WidgetAsset;

    /**
     * @return array<array-key, mixed>
     */
    abstract protected function getAssetSchema(Schema $configurator): array;

    /**
     * @return iterable<int, mixed>
     */
    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::WidgetAsset->value);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function make(Schema $configurator): array
    {
        return [
            Grid::make()
                ->relationship('asset')
                ->columnSpanFull()
                ->schema($this->extendAssetComponents($configurator, $this->getAssetSchema($configurator))),
        ];
    }

    /**
     * @param  array<int, mixed>  $components
     * @return array<int, mixed>
     */
    protected function extendAssetComponents(Schema $configurator, array $components): array
    {
        foreach (static::getExtenders() as $extender) {
            if ($extender instanceof WidgetAssetSchemaExtender) {
                $components = $extender->extendAssetComponents($configurator, $components);
            }
        }

        return $components;
    }
}
