<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\NameInput;
use Capell\Admin\Filament\Components\Forms\StatusToggle;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\LayoutModelEnum;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class WidgetSettingsSchema
{
    public static function make(Forms\Form $form, array $schema = []): array
    {
        return [
            Forms\Components\Hidden::make('is_key_changed_manually')
                ->default(false)
                ->dehydrated(false),

            NameInput::make('name')
                ->afterStateUpdated(function ($record, Get $get, Set $set, ?string $state): void {
                    if (! $record && ! $get('is_key_changed_manually') && filled($state)) {
                        $set('key', Str::slug($state));
                    }
                })
                ->lazy()
                ->required(),

            Forms\Components\TextInput::make('key')
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
                    ignoreRecord: $form->getOperation() !== 'replicate',
                    modifyRuleUsing: fn (Unique $rule) => $rule->withoutTrashed()
                ),

            WidgetTypeSelect::make('type_id')
                ->withRelation()
                ->withCreateForm()
                ->withEditForm(),

            ...$schema,

            Forms\Components\Grid::make()
                ->columns(['default' => 1, 'md' => 2, '2xl' => 1])
                ->schema([
                    Forms\Components\Grid::make()
                        ->columnSpan(1)
                        ->schema([
                            StatusToggle::make('status'),
                        ]),
                ]),
        ];
    }
}
