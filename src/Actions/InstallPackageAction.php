<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Support\Creator\LayoutCreator;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;
use Capell\LayoutBuilder\Support\LayoutModelRegistrar;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run()
 */
class InstallPackageAction
{
    use AsFake;
    use AsObject;

    public function handle(): void
    {
        LayoutModelRegistrar::register();

        $typeCreator = resolve(TypeCreator::class);
        $typeCreator->createBlockTypes();

        $typeCreator->createDefaultContentType();
        $typeCreator->createBuilderContentType();

        InstallLayoutBuilderBlockCatalogAction::run();

        $layoutCreator = resolve(LayoutCreator::class);
        $layoutCreator->setup();
    }
}
