<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Actions;

use Capell\TranslationManager\Contracts\TranslationFileStore;
use Capell\TranslationManager\Contracts\TranslationSourceResolver;
use Lorisleiva\Actions\Concerns\AsObject;

final class DuplicateLocaleAction
{
    use AsObject;

    public function handle(string $sourceKey, string $fromLocale, string $targetLocale): void
    {
        $source = resolve(TranslationSourceResolver::class)->source($sourceKey);

        resolve(TranslationFileStore::class)->duplicateLocale($source, $fromLocale, $targetLocale);
    }
}
