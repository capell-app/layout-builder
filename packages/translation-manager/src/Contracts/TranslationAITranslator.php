<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Contracts;

use Capell\TranslationManager\Data\AITranslationSuggestionData;
use Capell\TranslationManager\Data\TranslationEntryData;

interface TranslationAITranslator
{
    public function available(): bool;

    /**
     * @param  array<int, TranslationEntryData>  $entries
     * @return array<int, AITranslationSuggestionData>
     */
    public function translateSelected(string $sourceLocale, string $targetLocale, array $entries): array;
}
