<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Admin\Concerns\HasSchemaTypes;
use Capell\Admin\Contracts\SchemaTypeEnumInterface;
use Capell\Layout\Filament\Resources\Contents\Schemas\Types\DefaultContentSchema;
use Capell\Layout\Filament\Resources\Contents\Schemas\Types\TestimonialContentSchema;
use Capell\Layout\Filament\Resources\Layouts\Schemas\Types\Containers\DefaultLayoutContainerSchema;
use Capell\Layout\Filament\Resources\Layouts\Schemas\Types\Widgets\DefaultLayoutWidgetSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\Assets\ContentWidgetAssetForm;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\Assets\PageWidgetAssetForm;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\AssetsWidgetSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\CarouselWidgetSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\DefaultWidgetSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\NavigationWidgetSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\PageContentWidgetSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\ResultsWidgetSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\SystemWidgetSchema;

enum SchemaTypeEnum: string implements SchemaTypeEnumInterface
{
    use HasSchemaTypes;

    case Content = 'Contents';

    case LayoutContainer = 'LayoutContainers';

    case LayoutWidget = 'LayoutWidgets';

    case Widget = 'Widgets';

    case WidgetAsset = 'WidgetAssets';

    public function getSchemas(): array
    {
        return match ($this) {
            self::LayoutContainer => [
                DefaultLayoutContainerSchema::class,
            ],
            self::LayoutWidget => [
                DefaultLayoutWidgetSchema::class,
            ],
            self::Widget => [
                DefaultWidgetSchema::class,
                AssetsWidgetSchema::class,
                CarouselWidgetSchema::class,
                NavigationWidgetSchema::class,
                PageContentWidgetSchema::class,
                ResultsWidgetSchema::class,
                SystemWidgetSchema::class,
            ],
            self::WidgetAsset => [
                ContentWidgetAssetForm::class,
                PageWidgetAssetForm::class,
            ],
            self::Content => [
                DefaultContentSchema::class,
                TestimonialContentSchema::class,
            ],
        };
    }
}
