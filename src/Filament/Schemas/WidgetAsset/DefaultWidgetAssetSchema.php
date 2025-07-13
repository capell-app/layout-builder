<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\WidgetAsset;

use Capell\Admin\Filament\Resources\PageResource;
use Capell\Layout\Filament\Components\Forms\MediaSchema;
use Capell\Layout\Filament\Resources\ContentResource;
use Capell\Layout\Filament\Schemas\AbstractWidgetAssetSchema;
use Capell\Layout\Models\WidgetAsset;
use Filament\Forms;

class DefaultWidgetAssetSchema extends AbstractWidgetAssetSchema
{
    public static function make(Forms\Form $form): array
    {
        return [
            self::getAssetFormSchema($form),
        ];
    }

    protected static function getContentFormSchema(Forms\Form $form): array
    {
        return ContentResource::getFormSchema($form);
    }

    protected static function getFormSchema(WidgetAsset $record, Forms\Form $form): array
    {
        return match ($record->asset_type) {
            'content' => static::getContentFormSchema($form),
            'page' => static::getPageFormSchema($form),
            'media' => static::getMediaFormSchema(),
        };
    }

    protected static function getMediaFormSchema(): array
    {
        return MediaSchema::make();
    }

    protected static function getPageFormSchema(Forms\Form $form): array
    {
        return PageResource::getFormSchema($form);
    }

    protected static function getAssetFormSchema(Forms\Form $form): Forms\Components\Group
    {
        return Forms\Components\Group::make()
            ->relationship('asset')
            ->when(
                in_array($form->getOperation(), ['create', 'createOption'], true),
                fn (Forms\Components\Group $component): Forms\Components\Group => $component
                    ->dehydrated()
                    ->saveRelationshipsUsing(fn (): false => false),
            )
            ->mutateRelationshipDataBeforeCreateUsing(
                function (WidgetAsset $record, array $data, Forms\Get $get): array {
                    switch ($record->asset_type) {
                        case 'media':
                            if (blank($data['title'])) {
                                $data['title'] = pathinfo((string) $data['originalFilename'], PATHINFO_FILENAME);
                            }

                            unset($data['originalFilename']);
                            break;
                        case 'content':
                        case 'page':
                            $data['name'] = collect($get('asset.translations'))->first()['title'];

                            break;
                    }

                    return $data;
                }
            )
            ->schema(fn (WidgetAsset $record): array => static::getFormSchema($record, $form));
    }
}
