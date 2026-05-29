<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\NameInput;
use Capell\Admin\Filament\Components\Forms\StatusToggle;
use Capell\Core\Support\Slug\SlugGenerator;
use Capell\LayoutBuilder\Models\Widget;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class SettingsSchema
{
    /**
     * @param  array<array-key, mixed>  $components
     * @return array<array-key, mixed>
     */
    public static function make(Schema $configurator, array $components = []): array
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
                    table: Widget::class,
                    ignoreRecord: $configurator->getOperation() !== 'replicate',
                    modifyRuleUsing: fn (Unique $rule) => $rule->withoutTrashed(),
                ),

            TypeSelect::make('blueprint_id')
                ->withRelation()
                ->when(
                    $configurator->isCreating(),
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
                            DateTimePicker::make('visible_from')
                                ->label(__('capell-layout-builder::table.visible_from')),
                            DateTimePicker::make('visible_until')
                                ->label(__('capell-layout-builder::table.visible_until')),
                        ]),
                ]),
        ];
    }
}
