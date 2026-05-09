<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Actions;

use Capell\TranslationManager\Contracts\TranslationFileStore;
use Capell\TranslationManager\Contracts\TranslationSourceResolver;
use Capell\TranslationManager\Data\TranslationFileData;
use Lorisleiva\Actions\Concerns\AsObject;

final class ListTranslationFilesAction
{
    use AsObject;

    /**
     * @return array<int, TranslationFileData>
     */
    public function handle(string $sourceKey, string $sourceLocale, string $targetLocale): array
    {
        $source = resolve(TranslationSourceResolver::class)->source($sourceKey);

        return resolve(TranslationFileStore::class)->files($source, $sourceLocale, $targetLocale);
    }
}
