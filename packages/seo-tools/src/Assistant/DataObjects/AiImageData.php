<?php

declare(strict_types=1);

namespace Capell\SeoTools\Assistant\DataObjects;

final readonly class AiImageData
{
    /**
     * @param  array<string, string>  $contextFields  e.g. ['title' => '...', 'body' => '...']
     */
    public function __construct(
        public string $prompt,
        public array $contextFields = [],
        public string $size = '1024x1024',
        public ?string $model = null,
        public ?string $provider = null,
    ) {}
}
