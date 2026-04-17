<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\NameInput;
use Capell\Admin\Filament\Components\Forms\StatusToggle;
use Capell\Admin\Support\SlugGenerator;
use Capell\Core\Facades\CapellCore;
use Capell\Mosaic\Enums\ModelEnum;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class SettingsSchema
{
    public static function make(Schema $schema, array $components = []): array
    {
        return [
            NameInput::make('name')
                ->required()
                ->afterStateUpdatedJs(function (string $operation): string {
                    if (! in_array($operation, ['create', 'createOption', 'replicate'], true)) {
                        return '';
                    }

                    return SlugGenerator::slugifyState("\$state ?? ''", 'key');
                }),

            TextInput::make('key')
                ->label(__('capell-admin::form.key'))
                ->placeholder(__('capell-admin::generic.key_placeholder'))
                ->alphaDash()
                ->required()
                ->maxLength(128)
                ->unique(
                    table: CapellCore::getModel(ModelEnum::Widget->name),
                    ignoreRecord: $schema->getOperation() !== 'replicate',
                    modifyRuleUsing: fn (Unique $rule) => $rule->withoutTrashed(),
                ),

            TypeSelect::make('type_id')
                ->withRelation()
                ->when(
                    $schema->isCreating(),
                    fn (TypeSelect $component): TypeSelect => $component->withCreateForm(),
                    fn (TypeSelect $component): TypeSelect => $component->withEditForm(),
                ),

            ...$components,

            Grid::make()
                ->columns(['default' => 1, 'md' => 2, '2xl' => 1])
                ->schema([
                    Grid::make()
                        ->columnSpan(1)
                        ->schema([
                            StatusToggle::make('status'),
                        ]),
                ]),
        ];
    }
}
