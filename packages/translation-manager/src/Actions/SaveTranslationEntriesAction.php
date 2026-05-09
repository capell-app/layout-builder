<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Actions;

use Capell\TranslationManager\Contracts\TranslationFileStore;
use Capell\TranslationManager\Contracts\TranslationSourceResolver;
use Capell\TranslationManager\Data\TranslationWriteData;
use Lorisleiva\Actions\Concerns\AsObject;

final class SaveTranslationEntriesAction
{
    use AsObject;

    /**
     * @param  array<string, string|null>  $values
     */
    public function handle(string $sourceKey, string $fileKey, string $locale, array $values): void
    {
        $source = resolve(TranslationSourceResolver::class)->source($sourceKey);

        resolve(TranslationFileStore::class)->write(new TranslationWriteData(
            source: $source,
            fileKey: $fileKey,
            locale: $locale,
            values: $values,
        ));
    }
}
