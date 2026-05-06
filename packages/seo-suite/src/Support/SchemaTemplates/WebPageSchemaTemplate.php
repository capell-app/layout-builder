<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Support\SchemaTemplates;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Actions\PageMetaSchemaAction;
use Capell\SeoSuite\Contracts\SchemaTemplate;

class WebPageSchemaTemplate implements SchemaTemplate
{
    public function build(Page $page, Site $site, Language $language): array
    {
        $schema = PageMetaSchemaAction::run($page, $site, $language);
        $schema['@type'] = 'WebPage';

        return $schema;
    }

    public function requiredFields(Page $page, Site $site, Language $language): array
    {
        return ['@type', '@id', 'url', 'name'];
    }
}
