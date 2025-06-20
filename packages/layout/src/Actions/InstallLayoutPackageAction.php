<?php

declare(strict_types=1);

namespace Capell\Layout\Actions;

use Capell\Admin\Services\Creator\LayoutCreator;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
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
class InstallLayoutPackageAction
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

        $homeLayout = $layoutCreator->create(LayoutEnum::Home->value);

        $defaultLayout = Layout::default()->first();

        Site::all()->each(function (Site $site) use ($homeLayout, $defaultLayout): void {
            $homePage = $site->pages()->isHomePage()->first();

            if ($homePage->layout_id === $defaultLayout->id) {
                return;
            }

            $homePage->update(['layout_id' => $homeLayout->id]);
        });

        $layoutCreator = app(LayoutUpdater::class);
        $layoutCreator->setup();
    }
}
