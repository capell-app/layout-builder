<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Filament\Concerns\HasConfigurator;
use Capell\LayoutBuilder\Contracts\Extenders\WidgetAssetSchemaExtender;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\SchemaExtenderEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

abstract class AbstractWidgetAssetConfigurator implements ConfiguratorInterface
{
    use HasConfigurator;

    protected static ConfiguratorTypeEnumInterface $configuratorType = ConfiguratorTypeEnum::WidgetAsset;

    /**
     * @return array<int, Component | Action | ActionGroup | string | Htmlable>
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
     * @return array<int, Component | Action | ActionGroup | string | Htmlable>
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
     * @param  array<int, Component | Action | ActionGroup | string | Htmlable>  $components
     * @return array<int, Component | Action | ActionGroup | string | Htmlable>
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
