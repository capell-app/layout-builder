<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Support\SchemaTemplates;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Actions\PageMetaSchemaAction;
use Capell\SeoSuite\Contracts\SchemaTemplate;
use Capell\SeoSuite\Enums\SchemaTemplateTypeEnum;

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
