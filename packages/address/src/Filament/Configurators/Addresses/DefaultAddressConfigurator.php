<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Configurators\Addresses;

use Capell\Address\Enums\ConfiguratorTypeEnum;
use Capell\Address\Filament\Components\Forms\CountrySelect;
use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Filament\Components\Forms\DefaultToggle;
use Capell\Admin\Filament\Components\Forms\StatusToggle;
use Capell\Admin\Filament\Concerns\HasConfigurator;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class DefaultAddressConfigurator implements ConfiguratorInterface
{
    use HasConfigurator;

    protected static ConfiguratorTypeEnumInterface $configuratorType = ConfiguratorTypeEnum::Address;

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::Address->value);
    }

    public function make(Schema $configurator): array
    {
        return $this->getFormSchema($configurator);
    }

    private function getFormSchema(Schema $configurator): array
    {
        return [
            TextInput::make('name')
                ->label(__('capell-address::form.name'))
                ->required()
                ->columnSpanFull(),
            TextInput::make('line1')
                ->label(__('capell-address::form.address_line_1'))
                ->maxLength(128)
                ->required(),
            TextInput::make('line2')
                ->label(__('capell-address::form.address_line_2'))
                ->maxLength(128),
            TextInput::make('city')
                ->label(__('capell-address::form.city'))
                ->maxLength(64)
                ->required(),
            TextInput::make('state')
                ->label(__('capell-address::form.state'))
                ->maxLength(32)
                ->required(),
            TextInput::make('postal_code')
                ->label(__('capell-address::form.postal_code'))
                ->maxLength(16)
                ->required(),
            CountrySelect::make('country_id')
                ->when(
                    $configurator->isCreating(),
                    fn (CountrySelect $component): CountrySelect => $component->withCreateForm(),
                    fn (CountrySelect $component): CountrySelect => $component->withEditForm(),
                ),
            Grid::make()
                ->columnSpan(1)
                ->schema([
                    DefaultToggle::make('default'),
                    StatusToggle::make('status'),
                ]),
        ];
    }
}
