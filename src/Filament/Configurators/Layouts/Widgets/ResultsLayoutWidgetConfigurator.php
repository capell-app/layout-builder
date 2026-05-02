<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Configurators\Layouts\Widgets;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Filament\Concerns\HasConfigurator;
use Capell\Mosaic\Enums\ConfiguratorTypeEnum;
use Capell\Mosaic\Enums\SchemaExtenderEnum;
use Capell\Mosaic\Filament\Components\Forms\HtmlClassInput;
use Capell\Mosaic\Filament\Components\Forms\Widget\ResultsOverrideSchema;
use Filament\Schemas\Schema;

class ResultsLayoutWidgetConfigurator implements ConfiguratorInterface
{
    use HasConfigurator;

    protected static ConfiguratorTypeEnumInterface $configuratorType = ConfiguratorTypeEnum::LayoutWidget;

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::LayoutWidget->value);
    }

    public function make(Schema $configurator): array
    {
        return [
            ...ResultsOverrideSchema::make($configurator),
            HtmlClassInput::make('html_class'),
        ];
    }
}
