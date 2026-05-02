<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Configurators\Countries;

use Capell\Address\Enums\ConfiguratorTypeEnum;
use Capell\Address\Models\Country;
use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Filament\Components\Forms\DefaultToggle;
use Capell\Admin\Filament\Components\Forms\LanguageSelect;
use Capell\Admin\Filament\Components\Forms\StatusToggle;
use Capell\Admin\Filament\Concerns\HasConfigurator;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class DefaultCountryConfigurator implements ConfiguratorInterface
{
    use HasConfigurator;

    protected static ConfiguratorTypeEnumInterface $configuratorType = ConfiguratorTypeEnum::Country;

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::Country->value);
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
                ->required(),
            LanguageSelect::make('language_id')
                ->withRelationship(),
            TextInput::make('iso2')
                ->label(__('capell-address::form.iso2'))
                ->helperText(__('capell-admin::generic.iso_3166_2'))
                ->required()
                ->maxLength(2)
                ->unique(
                    table: Country::class,
                    ignoreRecord: $configurator->getOperation() !== 'replicate',
                    modifyRuleUsing: fn (Unique $rule) => $rule->withoutTrashed(),
                ),
            TextInput::make('iso3')
                ->label(__('capell-address::form.iso3'))
                ->helperText(__('capell-admin::generic.iso_3166_3'))
                ->required()
                ->maxLength(3)
                ->unique(
                    table: Country::class,
                    ignoreRecord: $configurator->getOperation() !== 'replicate',
                    modifyRuleUsing: fn (Unique $rule) => $rule->withoutTrashed(),
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
