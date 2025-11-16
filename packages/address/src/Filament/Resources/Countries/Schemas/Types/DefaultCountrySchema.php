<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Resources\Countries\Schemas\Types;

use Capell\Address\Enums\ModelEnum;
use Capell\Address\Enums\SchemaTypeEnum;
use Capell\Admin\Contracts\SchemaTypeEnumInterface;
use Capell\Admin\Contracts\TypeSchemaInterface;
use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Filament\Components\Forms\DefaultToggle;
use Capell\Admin\Filament\Components\Forms\LanguageSelect;
use Capell\Admin\Filament\Components\Forms\StatusToggle;
use Capell\Admin\Filament\Concerns\HasTypeSchema;
use Capell\Core\Facades\CapellCore;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class DefaultCountrySchema implements TypeSchemaInterface
{
    use HasTypeSchema;

    public static SchemaTypeEnumInterface $schemaType = SchemaTypeEnum::Country;

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::Country->value);
    }

    public function make(Schema $schema): array
    {
        return $this->getFormSchema($schema);
    }

    private function getFormSchema(Schema $schema): array
    {
        return [
            TextInput::make('name')
                ->label(__('capell-address::form.name'))
                ->required(),
            LanguageSelect::make('language_id'),
            TextInput::make('iso2')
                ->label(__('capell-address::form.iso2'))
                ->helperText(__('capell-admin::generic.iso_3166_2'))
                ->required()
                ->maxLength(2)
                ->unique(
                    table: CapellCore::getModel(ModelEnum::Country),
                    ignoreRecord: $schema->getOperation() !== 'replicate',
                    modifyRuleUsing: fn (Unique $rule) => $rule->withoutTrashed(),
                ),
            TextInput::make('iso3')
                ->label(__('capell-address::form.iso3'))
                ->helperText(__('capell-admin::generic.iso_3166_3'))
                ->required()
                ->maxLength(3)
                ->unique(
                    table: CapellCore::getModel(ModelEnum::Country),
                    ignoreRecord: $schema->getOperation() !== 'replicate',
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
