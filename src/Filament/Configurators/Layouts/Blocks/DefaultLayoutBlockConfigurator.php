<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Layouts\Blocks;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Filament\Components\Forms\Interactions\InteractionSettingsSchema;
use Capell\Admin\Filament\Components\Forms\Presentation\PresentationSettingsSchema;
use Capell\Admin\Filament\Concerns\HasConfigurator;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\SchemaExtenderEnum;
use Capell\LayoutBuilder\Filament\Components\Forms\HtmlClassInput;
use Filament\Schemas\Schema;

class DefaultLayoutBlockConfigurator implements ConfiguratorInterface
{
    use HasConfigurator;

    protected static ConfiguratorTypeEnumInterface $configuratorType = ConfiguratorTypeEnum::LayoutBlock;

    /**
     * @return iterable<int, mixed>
     */
    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::LayoutBlock->value);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function make(Schema $configurator): array
    {
        return [
            HtmlClassInput::make('html_class'),
            ...InteractionSettingsSchema::make('interactions'),
            ...PresentationSettingsSchema::make('presentation'),
        ];
    }
}
