<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types\Assets;

use Capell\Layout\Filament\Resources\Contents\Schemas\ContentForm;
use Filament\Schemas\Schema;
use Override;

class ContentWidgetAssetForm extends DefaultWidgetAssetSchema
{
    #[Override]
    public static function make(Schema $schema): array
    {
        return [
            self::getAssetFormSchema($schema, ContentForm::configure($schema)),
        ];
    }
}
