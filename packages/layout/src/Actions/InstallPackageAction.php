<?php

declare(strict_types=1);

namespace Capell\Layout\Actions;

use Capell\Admin\Services\Creator\LayoutCreator;
use Capell\Core\Models\Language;
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
        $typeCreator = app(TypeCreator::class);
        $typeCreator->createWidgetTypes();

        $typeCreator->createDefaultContentType();
        $typeCreator->createBuilderContentType();

        $widgetCreator = app(WidgetCreator::class);
        $widgetCreator->createWidgets(Language::all());

        $layoutCreator = app(LayoutCreator::class);
        $layoutCreator->setup();

        $layoutUpdater = app(LayoutUpdater::class);
        $layoutUpdater->setup();
    }
}
