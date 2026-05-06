<?php

declare(strict_types=1);

namespace Capell\PublishingStudio;

use Spatie\LaravelData\Data;

class CloneOptions extends Data
{
    public function __construct(
        public bool $copyDrafts = true,
        public bool $copySettings = true,
        public ?string $newName = null,
        public ?string $newSlug = null,
        public ?string $description = null,
    ) {}
}
