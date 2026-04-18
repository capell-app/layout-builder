<?php

declare(strict_types=1);

namespace Capell\Mosaic\Actions\Hero;

use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Type;
use Capell\Mosaic\Enums\LayoutTypeEnum;
use Capell\Mosaic\Filament\Resources\Sections\Schemas\Types\HeroContentSchema;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Type run()
 */
class CreateHeroContentTypeAction
{
    use AsFake;
    use AsObject;

    public function handle(): Type
    {
        /** @var class-string<Type> */
        $type = CapellCore::getModel(ModelEnum::Type->name);

        return $type::query()->firstOrCreate([
            'key' => 'hero',
            'type' => LayoutTypeEnum::Section,
        ], [
            'name' => __('capell-hero::generic.hero'),
            'admin' => [
                'schema' => HeroContentSchema::getKey(),
            ],
        ]);
    }
}
