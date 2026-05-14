<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Widget;

interface PublicWidgetPayloadResolver
{
    /**
     * @return array<string, mixed>
     */
    public function data(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): array;

    public function html(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): ?string;
}
