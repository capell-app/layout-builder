<?php

declare(strict_types=1);

namespace Capell\Blog\Providers;

use Capell\Blog\Support\Sitemap\ArchivesSitemap;
use Capell\Blog\Support\Sitemap\ArticlesSitemap;
use Capell\Blog\Support\Sitemap\TagsSitemap;
use Capell\Blog\Support\StaticSite\BlogStaticSiteExtension;
use Capell\Blog\View\Components\ArticleMeta;
use Capell\Blog\View\Components\AssetAfterTitle;
use Capell\Blog\View\Components\Footer\Pages;
use Capell\Blog\View\Components\Footer\Tags;
use Capell\Blog\View\Components\Page\BeforeContentTags;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\StaticSite\StaticSiteExtensionRegistry;
use Capell\Frontend\Data\RenderHookContext;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\SeoTools\Support\Sitemap\SitemapPageRegistry;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

final class FrontendServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(function (): void {
            if (! CapellCore::getPackage('capell-app/blog')->isInstalled()) {
                return;
            }

            $this->registerSitemapPages();
            $this->registerRenderHooks();
            $this->registerStaticSiteExtensions();
        });
    }

    private function registerSitemapPages(): void
    {
        $registry = resolve(SitemapPageRegistry::class);
        $registry->register('archives', ArchivesSitemap::class);
        $registry->register('articles', ArticlesSitemap::class);
        $registry->register('tags', TagsSitemap::class);
    }

    private function registerRenderHooks(): void
    {
        resolve(RenderHookRegistry::class)->register(
            RenderHookLocation::Footer,
            fn (RenderHookContext $context): ?View => resolve(Tags::class, [
                'item' => $context->item,
            ])
                ?->render(),
            target: 'footer.index',
        );

        resolve(RenderHookRegistry::class)->register(
            RenderHookLocation::Footer,
            fn (RenderHookContext $context): ?View => resolve(Pages::class, [
                'item' => $context->item,
            ])
                ?->render(),
            target: 'footer.index',
        );

        resolve(RenderHookRegistry::class)->register(
            RenderHookLocation::ArticleMeta,
            fn (RenderHookContext $context): ?View => resolve(ArticleMeta::class, [
                'item' => $context->item ?? null,
                'withAuthor' => $context->item['withAuthor'] ?? false,
                'author' => $context->item['author'] ?? null,
            ])
                ?->render(),
        );

        resolve(RenderHookRegistry::class)->register(
            RenderHookLocation::BeforeContent,
            fn (RenderHookContext $context): ?View => resolve(BeforeContentTags::class, [
                'item' => $context->item ?? null,
                'tags' => $context->item['tags'] ?? null,
            ])
                ?->render(),
        );

        resolve(RenderHookRegistry::class)->register(
            RenderHookLocation::AfterTitle,
            fn (RenderHookContext $context): ?View => resolve(AssetAfterTitle::class, [
                'publishDate' => $context->item['publishDate'] ?? null,
                'publishDatePosition' => $context->item['publishDatePosition'] ?? null,
                'tags' => $context->item['tags'] ?? null,
                'publishDateOutput' => $context->item['publishDateOutput'] ?? null,
            ])
                ?->render(),
        );
    }

    private function registerStaticSiteExtensions(): void
    {
        $registry = resolve(StaticSiteExtensionRegistry::class);

        if (! $registry->has('blog-tags-archives')) {
            $registry->register('blog-tags-archives', resolve(BlogStaticSiteExtension::class));
        }
    }
}
