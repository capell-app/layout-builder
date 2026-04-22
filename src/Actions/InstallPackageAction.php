<?php

declare(strict_types=1);

namespace Capell\Mosaic\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Support\Creator\LayoutCreator;
use Capell\Mosaic\Support\Creator\TypeCreator;
use Capell\Mosaic\Support\Creator\WidgetCreator;
use Capell\Mosaic\Support\LayoutModelRegistrar;
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
        $typeCreator->createWidgetTypes();

        $typeCreator->createDefaultContentType();
        $typeCreator->createBuilderContentType();

        $widgetCreator = resolve(WidgetCreator::class);
        $widgetCreator->createWidgets(Language::all());

        $layoutCreator = resolve(LayoutCreator::class);
        $layoutCreator->setup();
    }
}
