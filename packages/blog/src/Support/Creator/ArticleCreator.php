<?php

declare(strict_types=1);

namespace Capell\Blog\Support\Creator;

use Capell\Blog\Models\Article;
use Capell\Core\Contracts\ModelInterceptors\PageInterceptorInterface;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Enums\PageTypeEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\Creator\PageCreator;
use Illuminate\Support\Collection;

class ArticleCreator extends PageCreator
{
    public function __construct()
    {
        parent::__construct();

        $this->pageModel = Article::class;
    }

    public function createPage(array $data, Site $site, Collection $languages): Pageable
    {
        $defaults = [
            'name' => $data['name'],
            'layout_id' => $data['layout_id'] ?? $this->getLayout($data['layout_key'] ?? LayoutEnum::Default)->id,
            'site_id' => $site->id,
            'type_id' => $data['type_id'] ?? $this->getPageType($data['type_key'] ?? PageTypeEnum::Default)->id,
            'meta' => [
                'image_id' => $data['image_id'] ?? null,
            ],
            'visible_from' => $data['visible_from'] ?? null,
        ];

        /** @var Page $page */
        $page = CapellCore::createOrUpdateModel(
            $this->pageModel,
            [
                'name' => $data['name'],
                'layout_id' => $defaults['layout_id'],
                'site_id' => $site->id,
                'type_id' => $defaults['type_id'],
            ],
            fn (array $data): array => CapellCore::mergeModelInterceptorData($defaults, $data),
            PageInterceptorInterface::class,
        );

        $languages->each(function (Language $language) use ($data, $page): void {
            $translation_data = $data['translations'][$language->code] ?? [];

            $meta = $translation_data['meta'] ?? [];
            $meta['summary'] = $translation_data['summary'] ?? null;

            if (isset($translation_data['link_text'])) {
                $meta['link_text'] = $translation_data['link_text'];
            }

            if (! isset($meta['slug'])) {
                $meta['slug'] = $translation_data['slug'] ?? str($data['name'])->slug()->toString();
            }

            $attributes = [
                'title' => $translation_data['title'] ?? $data['name'],
                'content' => $translation_data['content'] ?? null,
                'meta' => $meta,
                'language_id' => $language->id,
            ];

            $translation = $page->translations()->firstOrNew(['language_id' => $language->id]);

            $translation->fill($attributes);

            if (isset($data['user_id'])) {
                $translation->forceFill([
                    'created_by' => $data['user_id'],
                    'updated_by' => $data['user_id'],
                ]);
            }

            $translation->save();
        });

        return $page;
    }
}
