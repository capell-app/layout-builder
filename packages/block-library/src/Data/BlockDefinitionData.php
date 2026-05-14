<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Data;

use Capell\ContentBlocks\Contracts\BlockRenderer;
use InvalidArgumentException;

final class BlockDefinitionData
{
    /**
     * @param  array<string, mixed>  $defaults
     * @param  class-string<BlockRenderer>|null  $renderer
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public string $category,
        public string $view,
        public array $defaults = [],
        public ?string $renderer = null,
        public bool $safeForPublicOutput = true,
        public string $sourcePackage = 'unknown',
    ) {
        foreach ([
            'key' => $this->key,
            'label' => $this->label,
            'category' => $this->category,
            'view' => $this->view,
        ] as $field => $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException(sprintf('Block definition [%s] must not be empty.', $field));
            }
        }
    }
}
