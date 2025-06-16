<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Layout;

use Capell\Admin\Filament\Components\Forms\Layout\LayoutDetailsSchema;
use Capell\Admin\Filament\Schemas\AbstractSchema;
use Capell\Core\Models\Layout;
use Capell\Layout\Enums\SchemaEnum;
use Capell\Layout\Livewire\LayoutBuilder;
use Filament\Forms;
use Filament\Forms\Get;

class DefaultLayoutSchema extends AbstractSchema
{
    protected static string $schemaType = SchemaEnum::Layout->value;

    public static function make(Forms\Form $form): array
    {
        return match ($form->getOperation()) {
            'create', 'createOption', 'replicate' => self::getCreateFormSchema($form),
            default => self::getEditFormSchema($form),
        };
    }

    private static function getCreateFormSchema(Forms\Form $form): array
    {
        return [
            Forms\Components\Grid::make()
                ->schema(LayoutDetailsSchema::make($form)),
        ];
    }

    private static function getEditFormSchema(Forms\Form $form): array
    {
        return [
            Forms\Components\Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    Forms\Components\Tabs\Tab::make(__('capell-admin::tab.layout_builder'))
                        ->visibleOn('edit')
                        ->schema([
                            Forms\Components\Livewire::make(
                                LayoutBuilder::class,
                                fn (Get $get, Layout $record): array => [
                                    'site_id' => $record->site_id,
                                    'layout_id' => $record->id,
                                ]
                            )
                                // TODO removing this breaks opening a selecting a 'widget content resource model' from the layout edit page.
                                ->lazy(),
                        ]),
                    Forms\Components\Tabs\Tab::make(__('capell-admin::tab.settings'))
                        ->columns()
                        ->schema(LayoutDetailsSchema::make($form)),
                ]),
        ];
    }
}
