<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Layouts\Widgets;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Filament\Concerns\HasConfigurator;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\SchemaExtenderEnum;
use Capell\LayoutBuilder\Filament\Components\Forms\HtmlClassInput;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\ResultsOverrideSchema;
use Filament\Schemas\Schema;

class ResultsLayoutWidgetConfigurator implements ConfiguratorInterface
{
    use HasConfigurator;

    protected static ConfiguratorTypeEnumInterface $configuratorType = ConfiguratorTypeEnum::Widget;

    /**
     * @return iterable<int, mixed>
     */
    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::Widget->value);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function make(Schema $configurator): array
    {
        return [
            ...ResultsOverrideSchema::make($configurator),
            HtmlClassInput::make('html_class'),
        ];
    }
}
