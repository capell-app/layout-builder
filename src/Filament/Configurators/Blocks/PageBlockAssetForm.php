<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Blocks;

use Capell\Admin\Enums\ConfiguratorTypeEnum;
use Capell\Admin\Filament\Configurators\Pages\DefaultPageConfigurator;
use Capell\Admin\Support\AdminSurfaceLookup;
use Filament\Schemas\Schema;
use Override;

class PageBlockAssetForm extends AbstractBlockAssetConfigurator
{
    #[Override]
    protected function getAssetSchema(Schema $configurator): array
    {
        $adminSchema = AdminSurfaceLookup::configurator(ConfiguratorTypeEnum::Page, DefaultPageConfigurator::getKey());

        return resolve($adminSchema)->make($configurator);
    }
}
