<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Widget;

use Capell\Admin\Actions\FixCuratorMetaDataAction;
use Capell\Admin\Filament\Components\Forms\CarouselSettingsSchema;
use Capell\Admin\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Filament\Forms;
use Override;

class CarouselWidgetSchema extends AssetsWidgetSchema
{
    #[Override]
    protected static function getSettingsTab(Forms\Form $form): Forms\Components\Tabs\Tab
    {
        return WidgetDisplayTab::make([
            Forms\Components\Grid::make()
                ->statePath('meta')
                ->mutateDehydratedStateUsing(function (array $state): array {
                    if (isset($state['background_image_id'])) {
                        $state['background_image_id'] = FixCuratorMetaDataAction::run($state['background_image_id']);
                    }

                    return $state;
                })
                ->schema([
                    Forms\Components\Fieldset::make(
                        __('capell-admin::generic.carousel_options')
                    )
                        ->columns(['default' => 2, 'xl' => 3])
                        ->schema(CarouselSettingsSchema::make()),
                    WidgetDisplaySection::make([
                        ColorSchemeComponent::make('color_scheme'),
                    ]),
                    WidgetComponentFilesSection::make(),
                ]),
        ]);
    }
}
