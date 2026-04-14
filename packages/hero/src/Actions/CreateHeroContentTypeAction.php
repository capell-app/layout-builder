<?php

declare(strict_types=1);

namespace Capell\Hero\Actions;

use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Type;
use Capell\Hero\Filament\Resources\Contents\Schemas\Types\HeroContentSchema;
use Capell\Layout\Enums\LayoutTypeEnum;
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
            'type' => LayoutTypeEnum::Content,
        ], [
            'name' => __('capell-hero::generic.hero'),
            'admin' => [
                'schema' => HeroContentSchema::getKey(),
            ],
        ]);
    }
}
