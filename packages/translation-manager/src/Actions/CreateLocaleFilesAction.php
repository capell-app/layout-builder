<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Actions;

use Capell\TranslationManager\Contracts\TranslationFileStore;
use Capell\TranslationManager\Contracts\TranslationSourceResolver;
use Lorisleiva\Actions\Concerns\AsObject;

final class CreateLocaleFilesAction
{
    use AsObject;

    public function handle(string $sourceKey, string $locale, ?string $sourceLocale = null): void
    {
        $source = resolve(TranslationSourceResolver::class)->source($sourceKey);

        resolve(TranslationFileStore::class)->createLocale(
            $source,
            $locale,
            $sourceLocale ?? (string) config('capell-translation-manager.source_locale', 'en'),
        );
    }
}
