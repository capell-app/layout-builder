<?php

declare(strict_types=1);

namespace Capell\Assistant\Data;

use Spatie\LaravelData\Data;

class AssistantRunData extends Data
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $moduleKey,
        public string $capabilityKey,
        public string $prompt,
        public array $context = [],
    ) {}
}
