<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Models\Element;

interface PublicElementPayloadContributor
{
    public const TAG = 'capell.layout_builder.public_element_payload_contributor';

    public function priority(): int;

    /**
     * @return array<string, mixed>
     */
    public function data(Element $element, Page $page, Language $language, string $containerKey, int $occurrence): array;

    public function html(Element $element, Page $page, Language $language, string $containerKey, int $occurrence): ?string;
}
