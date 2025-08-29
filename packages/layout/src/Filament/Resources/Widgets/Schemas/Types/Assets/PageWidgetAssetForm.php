<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types\Assets;

use Capell\Admin\Filament\Resources\Pages\PageResource;
use Filament\Schemas\Schema;
use Override;

class PageWidgetAssetForm extends DefaultWidgetAssetSchema
{
    #[Override]
    public static function make(Schema $schema): array
    {
        return [
            self::getAssetFormSchema($schema, PageResource::getFormSchema($schema)),
        ];
    }
}
