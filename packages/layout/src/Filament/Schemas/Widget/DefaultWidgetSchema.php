<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Widget;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\ImageMediaPicker;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetTranslationsRepeater;
use Filament\Forms;

class DefaultWidgetSchema extends AbstractWidgetSchema
{
    public static function make(Forms\Form $form): array
    {
        $operation = $form->getOperation();

        return match ($operation) {
            'create', 'createOption', 'replicate' => [
                WidgetTranslationsRepeater::make($form),
                Forms\Components\Group::make()
                    ->statePath('meta')
                    ->schema(self::getExtraSchema()),
            ],
            'editOption' => [
                WidgetTranslationsRepeater::make($form),
                Forms\Components\Group::make()
                    ->statePath('meta')
                    ->schema(self::getExtraSchema()),
            ],
            default => [
                FixedWidthSidebar::make()
                    ->mainSchema([
                        WidgetTranslationsRepeater::make($form),
                        Forms\Components\Group::make()
                            ->statePath('meta')
                            ->schema(self::getExtraSchema()),
                    ])
                    ->sidebarSchema([
                        Forms\Components\Section::make()
                            ->columns(1)
                            ->schema(WidgetSettingsSchema::make($form)),
                    ]),
            ],
        };
    }

    protected static function getExtraSchema(): array
    {
        return [
            ImageMediaPicker::make('image_id')
                ->label(__('capell-admin::form.image'))
                ->relationship(relationshipName: 'image', titleColumnName: 'name'),
        ];
    }
}
