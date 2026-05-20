<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Models\Block;

interface PublicBlockPayloadContributor
{
    public const string TAG = 'capell.layout_builder.public_block_payload_contributor';

    public function priority(): int;

    /**
     * @return array<string, mixed>
     */
    public function data(Block $block, Page $page, Language $language, string $containerKey, int $occurrence): array;

    public function html(Block $block, Page $page, Language $language, string $containerKey, int $occurrence): ?string;
}
