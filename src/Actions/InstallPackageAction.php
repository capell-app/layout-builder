<?php

declare(strict_types=1);

namespace Capell\Layout\Actions;

use Capell\Admin\Services\Creator\LayoutCreator;
use Capell\Core\Models\Language;
use Capell\Layout\LayoutModelRegistrar;
use Capell\Layout\Services\Creator\LayoutUpdater;
use Capell\Layout\Services\Creator\TypeCreator;
use Capell\Layout\Services\Creator\WidgetCreator;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run()
 */
class InstallPackageAction
{
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

        $layoutUpdater = resolve(LayoutUpdater::class);
        $layoutUpdater->setup();
    }
}
