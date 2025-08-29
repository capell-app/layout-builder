<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Layouts\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\Layout\Tab\LayoutSettingsTab;
use Capell\Core\Models\Layout;
use Capell\Layout\Livewire\LayoutBuilder;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Override;

class DefaultLayoutSchema extends \Capell\Admin\Filament\Resources\Layouts\Schemas\Types\DefaultLayoutSchema
{
    #[Override]
    protected static function getTabs(Schema $schema): array
    {
        return [
            static::getBuilderTab(),
            LayoutSettingsTab::make($schema),
        ];
    }

    protected static function getBuilderTab(): Tab
    {
        return Tab::make(__('capell-admin::tab.layout_builder'))
            ->visibleOn('edit')
            ->schema([
                Livewire::make(
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
