<?php

declare(strict_types=1);

namespace Capell\Blog\Actions;

use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\WidgetTypeEnum;
use Exception;
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
        $blogCreator = resolve(BlogCreator::class);

        // Widgets
        $blogCreator->createArticleWidget($blogCreator->createArticleWidgetType());

        $latestArticlesWidget = $blogCreator->createLatestArticlesWidget();
        $archivesWidget = $blogCreator->createArchivesWidget();

        // Layouts
        $blogCreator->createArticleLayout();
        $blogCreator->createArchivesLayout();
        $blogCreator->createBlogPageLayout();
        $blogCreator->createTagsLayout();

        $layouts = [
            LayoutEnum::Results,
            LayoutEnum::Default,
        ];

        foreach ($layouts as $layoutKey) {
            $layout = Layout::query()->firstWhere('key', $layoutKey);

            throw_unless($layout, Exception::class, sprintf('Layout with key %s not found.', $layoutKey->value));

            $containers = $layout->containers;

            if (! in_array($latestArticlesWidget->key, array_column($containers['sidebar']['widgets'], 'widget_key'), true)) {
                $containers['sidebar']['widgets'] = array_filter(
                    $containers['sidebar']['widgets'],
                    fn (array $widget): bool => $widget['widget_key'] !== 'latest-pages',
                );

                $containers['sidebar']['widgets'][] = [
                    'widget_key' => $latestArticlesWidget->key,
                ];
            }

            if (in_array($layoutKey, ['results', 'tags'], true) && ! in_array($archivesWidget->key, array_column($containers['sidebar']['widgets'], 'widget_key'), true)) {
                $containers['sidebar']['widgets'][] = [
                    'widget_key' => $archivesWidget->key,
                ];
            }

            $layout->update(['containers' => $containers]);
        }

        // Page Types
        $blogCreator->createArticlePageType();
        $blogCreator->createArchivePageType();
        $blogCreator->createBlogPageType();
        $blogCreator->createTagPageType();

        $resultsWidgetType = Type::query()
            ->firstWhere(['key' => WidgetTypeEnum::Results, 'type' => LayoutTypeEnum::Widget]);

        Site::with('languages')->each(function (Site $site) use ($blogCreator, $resultsWidgetType): void {
            $blogCreator->createTagsWidget($site->languages);

            $blogCreator->relatedArticlesWidget(type: $resultsWidgetType);

            CreateBlogPagesAction::run($site);
        });
    }
}
