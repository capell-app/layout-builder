<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Configurators\Sections\DefaultSectionConfigurator;
use Filament\Schemas\Schema;
use Override;

class SectionWidgetAssetForm extends AbstractWidgetAssetConfigurator
{
    #[Override]
    protected function getAssetSchema(Schema $configurator): array
    {
        $adminSchema = AdminSurfaceLookup::configurator(ConfiguratorTypeEnum::Section, DefaultSectionConfigurator::getKey());

        return resolve($adminSchema)->make($configurator);
    }
}
