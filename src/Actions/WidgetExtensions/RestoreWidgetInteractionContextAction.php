<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\WidgetExtensions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Data\FrontendContext;
use Capell\Frontend\Data\FrontendRenderContextData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

/** @method static FrontendRenderContextData|null run(array<string, mixed> $envelope) */
final class RestoreWidgetInteractionContextAction
{
    use AsFake;
    use AsObject;

    /** @param array<string, mixed> $envelope */
    public function handle(array $envelope): ?FrontendRenderContextData
    {
        try {
            if (($envelope['version'] ?? null) !== 1 || ($envelope['purpose'] ?? null) !== 'widget-interaction') {
                return null;
            }

            $siteId = $this->positiveId($envelope['site_id'] ?? null);
            $pageId = $this->positiveId($envelope['page_id'] ?? null);
            $languageId = $this->positiveId($envelope['language_id'] ?? null);
            $pageType = $envelope['page_type'] ?? null;
            if ($siteId === null || $pageId === null || $languageId === null || ! is_string($pageType)) {
                return null;
            }

            $modelClass = Relation::getMorphedModel($pageType) ?? $pageType;
            if (! class_exists($modelClass) || ! is_a($modelClass, Model::class, true) || ! is_a($modelClass, Pageable::class, true)) {
                return null;
            }

            $site = Site::query()->find($siteId);
            $page = $modelClass::query()->find($pageId);
            $language = Language::query()->find($languageId);
            if (! $site instanceof Site || ! $page instanceof Model || ! $page instanceof Pageable || ! $language instanceof Language) {
                return null;
            }

            $siteHasLanguage = $this->positiveId($site->getAttribute('language_id')) === $languageId
                || $site->languages()->whereKey($languageId)->exists();
            if ($this->positiveId($page->getAttribute('site_id')) !== $siteId || ! $siteHasLanguage) {
                return null;
            }

            $translation = $page->translations()->where('language_id', $languageId)->first();
            if (! $translation instanceof Model) {
                return null;
            }
            $page->setRelation('translation', $translation);

            $layout = $this->optionalLayout($envelope['layout_id'] ?? null);
            $theme = $this->optionalTheme($envelope['theme_id'] ?? null);
            if ($layout === false || $theme === false) {
                return null;
            }

            if ($layout instanceof Layout) {
                $layoutSiteId = $layout->getAttribute('site_id');
                if ($layoutSiteId !== null && $this->positiveId($layoutSiteId) !== $siteId) {
                    return null;
                }
            }

            $pageLayoutId = $page->getAttribute('layout_id');
            if ($pageLayoutId !== null && (! $layout instanceof Layout || $this->positiveId($pageLayoutId) !== $this->positiveId($layout->getKey()))) {
                return null;
            }

            $siteThemeId = $site->getAttribute('theme_id');
            if ($siteThemeId !== null && (! $theme instanceof Theme || $this->positiveId($siteThemeId) !== $this->positiveId($theme->getKey()))) {
                return null;
            }

            $context = new FrontendRenderContextData($page, $site, $language, $layout, $theme);
            app()->instance(FrontendContextReader::class, new FrontendContext(
                site: $site,
                language: $language,
                page: $page,
                layout: $layout,
                theme: $theme,
                params: [],
                slug: null,
            ));

            return $context;
        } catch (Throwable) {
            return null;
        }
    }

    private function positiveId(mixed $value): ?int
    {
        return is_int($value) && $value > 0 ? $value : null;
    }

    private function optionalLayout(mixed $identifier): Layout|false|null
    {
        if ($identifier === null) {
            return null;
        }

        $modelId = $this->positiveId($identifier);

        return $modelId === null ? false : (Layout::query()->find($modelId) ?? false);
    }

    private function optionalTheme(mixed $identifier): Theme|false|null
    {
        if ($identifier === null) {
            return null;
        }

        $modelId = $this->positiveId($identifier);

        return $modelId === null ? false : (Theme::query()->find($modelId) ?? false);
    }
}
