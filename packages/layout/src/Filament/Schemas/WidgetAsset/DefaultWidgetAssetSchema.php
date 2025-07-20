<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\WidgetAsset;

use Capell\Admin\Filament\Resources\PageResource;
use Capell\Layout\Filament\Components\Forms\MediaSchema;
use Capell\Layout\Filament\Resources\ContentResource;
use Capell\Layout\Filament\Schemas\AbstractWidgetAssetSchema;
use Capell\Layout\Models\WidgetAsset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DefaultWidgetAssetSchema extends AbstractWidgetAssetSchema
{
    public static function make(Schema $schema): array
    {
        return [
            self::getAssetFormSchema($schema),
        ];
    }

    protected static function getContentFormSchema(Schema $schema): array
    {
        return ContentResource::getFormSchema($schema);
    }

    protected static function getFormSchema(WidgetAsset $record, Schema $schema): array
    {
        return match ($record->asset_type) {
            'content' => static::getContentFormSchema($schema),
            'page' => static::getPageFormSchema($schema),
            'media' => static::getMediaFormSchema(),
        };
    }

    protected static function getMediaFormSchema(): array
    {
        return MediaSchema::make();
    }

    protected static function getPageFormSchema(Schema $schema): array
    {
        return PageResource::getFormSchema($schema);
    }

    protected static function getAssetFormSchema(Schema $schema): Group
    {
        return Group::make()
            ->relationship('asset')
            ->when(
                in_array($schema->getOperation(), ['create', 'createOption'], true),
                fn (Group $component): Group => $component
                    ->dehydrated()
                    ->saveRelationshipsUsing(fn (): false => false),
            )
            ->mutateRelationshipDataBeforeCreateUsing(
                function (WidgetAsset $record, array $data, Get $get): array {
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
            ->schema(fn (WidgetAsset $record): array => static::getFormSchema($record, $schema));
    }
}
