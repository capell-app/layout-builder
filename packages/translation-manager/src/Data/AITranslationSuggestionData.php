<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Data;

use Spatie\LaravelData\Data;

final class AITranslationSuggestionData extends Data
{
    public function __construct(
        public readonly string $key,
        public readonly string $value,
    ) {}
}
