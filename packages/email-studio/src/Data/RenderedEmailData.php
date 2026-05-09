<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Data;

use Spatie\LaravelData\Data;

class RenderedEmailData extends Data
{
    /**
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public string $subject,
        public ?string $previewText,
        public string $html,
        public ?string $text,
        public array $headers = [],
    ) {}
}
