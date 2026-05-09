<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Actions;

use Capell\TranslationManager\Contracts\TranslationFileStore;
use Capell\TranslationManager\Contracts\TranslationSourceResolver;
use Capell\TranslationManager\Data\TranslationEntryData;
use Lorisleiva\Actions\Concerns\AsObject;

final class LoadTranslationComparisonAction
{
    use AsObject;

    /**
     * @return array<int, TranslationEntryData>
     */
    public function handle(string $sourceKey, string $fileKey, string $sourceLocale, string $targetLocale): array
    {
        $source = resolve(TranslationSourceResolver::class)->source($sourceKey);

        return resolve(TranslationFileStore::class)->comparison($source, $fileKey, $sourceLocale, $targetLocale);
    }
}
