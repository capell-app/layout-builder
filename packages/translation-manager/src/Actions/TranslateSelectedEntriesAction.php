<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Actions;

use Capell\TranslationManager\Contracts\TranslationAITranslator;
use Capell\TranslationManager\Data\AITranslationSuggestionData;
use Capell\TranslationManager\Data\TranslationEntryData;
use Lorisleiva\Actions\Concerns\AsObject;

final class TranslateSelectedEntriesAction
{
    use AsObject;

    /**
     * @param  array<int, TranslationEntryData>  $entries
     * @param  array<int, string>  $selectedKeys
     * @return array<int, AITranslationSuggestionData>
     */
    public function handle(string $sourceLocale, string $targetLocale, array $entries, array $selectedKeys): array
    {
        $translator = resolve(TranslationAITranslator::class);

        if (! $translator->available()) {
            return [];
        }

        $selected = collect($entries)
            ->filter(fn (TranslationEntryData $entry): bool => in_array($entry->key, $selectedKeys, true))
            ->filter(fn (TranslationEntryData $entry): bool => $entry->editable && $entry->sourceValue !== null && $entry->sourceValue !== '')
            ->values()
            ->all();

        return $translator->translateSelected($sourceLocale, $targetLocale, $selected);
    }
}
