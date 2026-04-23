<?php

declare(strict_types=1);

namespace Capell\SeoTools\Assistant\Support;

use Capell\Core\Models\Translation;

final class AiActionInput
{
    public function __construct(
        public Translation $translation,
        /** @var array<string, mixed> */
        public array $options = [],
    ) {}
}
