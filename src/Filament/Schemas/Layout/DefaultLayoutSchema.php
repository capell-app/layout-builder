<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Layout;

use Capell\Admin\Filament\Components\Forms\Layout\Tab\LayoutSettingsTab;
use Capell\Core\Models\Layout;
use Capell\Layout\Livewire\LayoutBuilder;
use Filament\Forms;
use Filament\Forms\Get;

class DefaultLayoutSchema extends \Capell\Admin\Filament\Schemas\Layout\DefaultLayoutSchema
{
    protected static function getTabs(Forms\Form $form): array
    {
        return [
            static::getBuilderTab(),
            LayoutSettingsTab::make($form),
        ];
    }

    protected static function getBuilderTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('capell-admin::tab.layout_builder'))
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
            ]);
    }
}
