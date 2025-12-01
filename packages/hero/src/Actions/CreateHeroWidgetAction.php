<?php

declare(strict_types=1);

namespace Capell\Hero\Actions;

use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Type;
use Capell\Hero\Enums\WidgetComponentEnum;
use Capell\Hero\Filament\Resources\Widgets\Schemas\Types\HeroWidgetSchema;
use Capell\Layout\Enums\AssetEnum;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Enums\WidgetTypeEnum;
use Capell\Layout\Models\Widget;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Widget run()
 */
class CreateHeroWidgetAction
{
    use AsObject;

    public function handle(): Widget
    {
        /** @var class-string<Widget> $widgetModel */
        $widgetModel = CapellCore::getModel(ModelEnum::Widget->name);

        /** @var class-string<Type> */
        $typeModel = CapellCore::getModel(CoreModelEnum::Type->name);

        $type = $typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::Assets,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => 'Assets Widget',
        ]);

        return $widgetModel::query()->firstOrCreate([
            'key' => 'hero',
        ], [
            'name' => __('capell-hero::generic.hero'),
            'type_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::Hero->value,
                'heading_size' => 'h1',
                'height' => 'large',
                'carousel_fade' => true,
                'carousel_arrows' => false,
                'carousel_pagination' => true,
                'carousel_loop' => true,
                'carousel_auto' => true,
                'carousel_auto_delay' => 50000,
                'color_scheme' => 'dark',
            ],
            'admin' => [
                'icon' => 'heroicon-o-gift',
                'schema' => HeroWidgetSchema::getKey(),
                'asset_types' => [AssetEnum::Content->value],
            ],
        ]);
    }
}
