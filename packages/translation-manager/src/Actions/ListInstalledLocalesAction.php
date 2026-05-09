<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Actions;

use Capell\TranslationManager\Contracts\TranslationFileStore;
use Capell\TranslationManager\Contracts\TranslationSourceResolver;
use Capell\TranslationManager\Data\LocaleSummaryData;
use Lorisleiva\Actions\Concerns\AsObject;

final class ListInstalledLocalesAction
{
    use AsObject;

    /**
     * @return array<int, LocaleSummaryData>
     */
    public function handle(string $sourceKey): array
    {
        $source = resolve(TranslationSourceResolver::class)->source($sourceKey);

        return resolve(TranslationFileStore::class)->locales($source);
    }
}
