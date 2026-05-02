<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Configurators\Widgets;

use Capell\Admin\Enums\ConfiguratorTypeEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Configurators\Pages\DefaultPageConfigurator;
use Filament\Schemas\Schema;
use Override;

class PageWidgetAssetForm extends AbstractWidgetAssetConfigurator
{
    #[Override]
    protected function getAssetSchema(Schema $configurator): array
    {
        $adminSchema = CapellAdmin::getConfigurator(ConfiguratorTypeEnum::Page, DefaultPageConfigurator::getKey());

        return resolve($adminSchema)->make($configurator);
    }
}
