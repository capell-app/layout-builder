<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Layouts\Elements;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Filament\Concerns\HasConfigurator;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\SchemaExtenderEnum;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\ResultsOverrideSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\HtmlClassInput;
use Filament\Schemas\Schema;

class ResultsLayoutElementConfigurator implements ConfiguratorInterface
{
    use HasConfigurator;

    protected static ConfiguratorTypeEnumInterface $configuratorType = ConfiguratorTypeEnum::LayoutElement;

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::LayoutElement->value);
    }

    public function make(Schema $configurator): array
    {
        return [
            ...ResultsOverrideSchema::make($configurator),
            HtmlClassInput::make('html_class'),
        ];
    }
}
