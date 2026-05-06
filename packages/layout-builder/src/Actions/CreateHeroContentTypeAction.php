<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

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
            'type' => 'section',
        ], [
            'name' => __('capell-layout-builder::generic.hero'),
            'admin' => [
                'configurator' => 'hero-section',
            ],
        ]);
    }
}
