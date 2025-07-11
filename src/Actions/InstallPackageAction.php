<?php

declare(strict_types=1);

namespace Capell\Layout\Actions;

use Capell\Admin\Services\Creator\LayoutCreator;
use Capell\Core\Models\Language;
use Capell\Layout\Enums\LayoutEnum;
use Capell\Layout\Services\Creator\ContentCreator;
use Capell\Layout\Services\Creator\LayoutCreator as LayoutCreatorService;
use Capell\Layout\Services\Creator\LayoutUpdater;
use Capell\Layout\Services\Creator\WidgetCreator;
use Capell\Layout\Services\Creator\WidgetTypeCreator;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run()
 */
class InstallPackageAction
{
    use AsObject;

    public function handle(): void
    {
        $widgetTypeCreator = app(WidgetTypeCreator::class);
        $widgetTypeCreator->createWidgetTypes();

        $contentCreator = app(ContentCreator::class);
        $contentCreator->createContentTypes();

        $widgetCreator = app(WidgetCreator::class);
        $widgetCreator->createWidgets(Language::all());

        $layoutCreator = app(LayoutCreator::class);
        $layoutCreator->setup();

        $layoutCreator = app(LayoutCreatorService::class);
        $layoutCreator->create(LayoutEnum::Home->value);

        $layoutUpdater = app(LayoutUpdater::class);
        $layoutUpdater->setup();

        CreateThemeAction::run();
    }
}
