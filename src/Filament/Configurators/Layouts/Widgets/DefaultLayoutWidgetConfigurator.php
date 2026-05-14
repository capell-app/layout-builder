<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Layouts\Widgets;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Filament\Concerns\HasConfigurator;
use Capell\Core\LayoutBuilder\Enums\SchemaExtenderEnum;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Components\Forms\HtmlClassInput;
use Filament\Schemas\Schema;

class DefaultLayoutWidgetConfigurator implements ConfiguratorInterface
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
            HtmlClassInput::make('html_class'),
        ];
    }
}
