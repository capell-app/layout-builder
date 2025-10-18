<?php

declare(strict_types=1);

namespace Capell\Blog\Actions;

use Capell\Admin\Enums\LayoutEnum;
use Capell\Blog\Services\BlogCreator;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
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
        // Widgets
        BlogCreator::createArticleWidget(BlogCreator::createArticleWidgetType());
        $latestArticlesWidget = BlogCreator::createLatestArticlesWidget();
        $archivesWidget = BlogCreator::createArchivesListWidget();

        // Layouts
        BlogCreator::createArticleLayout();
        BlogCreator::createArchivesLayout();
        BlogCreator::createBlogPageLayout();

        $layouts = [
            LayoutEnum::Results,
            LayoutEnum::Tags,
            LayoutEnum::Default,
        ];

        foreach ($layouts as $layoutKey) {
            $layout = Layout::query()->firstWhere('key', $layoutKey);

            throw_unless($layout, new Exception(sprintf('Layout with key %s not found.', $layoutKey->value)));

            $containers = $layout->containers;

            if (! in_array($latestArticlesWidget->key, array_column($containers['sidebar']['widgets'], 'widget_key'), true)) {
                $containers['sidebar']['widgets'] = array_filter(
                    $containers['sidebar']['widgets'],
                    fn (array $widget): bool => $widget['widget_key'] !== 'latest-pages'
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

        // Page Forms
        BlogCreator::createArticlePageType();
        BlogCreator::createArchivePageType();
        BlogCreator::createBlogPageType();

        Site::with('languages')->each(fn (Site $site) => CreateBlogPagesAction::run($site));
    }
}
