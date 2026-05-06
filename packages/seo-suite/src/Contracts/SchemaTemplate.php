<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Contracts;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;

interface SchemaTemplate
{
    /**
     * @return array<string, mixed>
     */
    public function build(Page $page, Site $site, Language $language): array;

    /**
     * @return list<string>
     */
    public function requiredFields(Page $page, Site $site, Language $language): array;
}
