<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Support;

use Capell\TranslationManager\Contracts\TranslationAITranslator;

final class NullTranslationAITranslator implements TranslationAITranslator
{
    public function available(): bool
    {
        return false;
    }

    public function translateSelected(string $sourceLocale, string $targetLocale, array $entries): array
    {
        return [];
    }
}
