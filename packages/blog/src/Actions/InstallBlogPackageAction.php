<?php

declare(strict_types=1);

namespace Capell\Blog\Actions;

use Capell\Admin\Enums\LayoutEnum;
use Capell\Blog\Services\BlogCreator;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\WidgetTypeEnum;
use Exception;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run()
 */
class InstallBlogPackageAction
{
    use AsObject;

    public function handle(): void
    {
        $blogCreator = app(BlogCreator::class);

        // Widgets
        $blogCreator->createArticleWidget($blogCreator->createArticleWidgetType());

        $latestArticlesWidget = $blogCreator->createLatestArticlesWidget();
        $archivesWidget = $blogCreator->createArchivesListWidget();

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
            ->firstWhere(['key' => WidgetTypeEnum::PageResults, 'type' => LayoutTypeEnum::Widget]);

        Site::with('languages')->each(function (Site $site) use ($blogCreator, $resultsWidgetType): void {
            $blogCreator->createTagsWidget($site->languages);

            $blogCreator->relatedPagesWidget(type: $resultsWidgetType, languages: $site->languages);

            CreateBlogPagesAction::run($site);
        });
    }
}
