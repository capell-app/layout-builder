<?php

declare(strict_types=1);

namespace Capell\SeoTools\Support\SchemaTemplates;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoTools\Actions\PageMetaSchemaAction;
use Capell\SeoTools\Contracts\SchemaTemplate;
use Capell\SeoTools\Enums\SchemaTemplateTypeEnum;

class ArticleSchemaTemplate implements SchemaTemplate
{
    public function build(Page $page, Site $site, Language $language): array
    {
        /** @var string|null $schemaType */
        $schemaType = data_get($page, 'type.meta.schema.type');

        if (! SchemaTemplateTypeEnum::Article->matchesSchemaType($schemaType)) {
            return [];
        }

        return PageMetaSchemaAction::run($page, $site, $language);
    }

    public function requiredFields(Page $page, Site $site, Language $language): array
    {
        return ['@type', '@id', 'url', 'headline', 'description', 'datePublished', 'author', 'publisher'];
    }
}
