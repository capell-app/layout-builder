<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\NameInput;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\LayoutModelEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class CreateWidgetDetailsSchema
{
    public static function make(Schema $schema): array
    {
        return [
            Grid::make()
                ->visibleOn(['create', 'createOption', 'replicate'])
                ->schema(self::getSchema($schema)),
        ];
    }

    private static function getSchema(Schema $schema): array
    {
        return [
            Hidden::make('is_key_changed_manually')
                ->default(false)
                ->dehydrated(false),

            NameInput::make('name')
                ->lazy()
                ->afterStateUpdated(function ($record, Get $get, Set $set, ?string $state): void {
                    if (! $record && ! $get('is_key_changed_manually') && filled($state)) {
                        $set('key', Str::slug($state));
                    }
                }),

            TextInput::make('key')
                ->label(__('capell-admin::form.key'))
                ->placeholder(__('capell-admin::generic.key_placeholder'))
                ->afterStateUpdated(function (Set $set, $state): void {
                    $set('is_key_changed_manually', (bool) $state);
                })
                ->alphaDash()
                ->required()
                ->maxLength(128)
                ->unique(
                    table: CapellCore::getModel(LayoutModelEnum::Widget->name),
                    ignoreRecord: $schema->getOperation() !== 'replicate',
                    modifyRuleUsing: fn (Unique $rule) => $rule->withoutTrashed()
                ),

            WidgetTypeSelect::make('type_id')
                ->live()
                ->withRelation()
                ->withCreateForm(),
        ];
    }
}
