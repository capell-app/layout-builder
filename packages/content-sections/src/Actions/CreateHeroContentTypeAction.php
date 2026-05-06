<?php

declare(strict_types=1);

namespace Capell\ContentSections\Actions;

use Capell\ContentSections\Enums\LayoutTypeEnum;
use Capell\ContentSections\Filament\Configurators\Sections\HeroSectionConfigurator;
use Capell\Core\Models\Type;
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
        $type = Type::class;

        return $type::query()->firstOrCreate([
            'key' => 'hero',
            'type' => LayoutTypeEnum::Section,
        ], [
            'name' => __('capell-content-sections::generic.hero'),
            'admin' => [
                'configurator' => HeroSectionConfigurator::getKey(),
            ],
        ]);
    }
}
