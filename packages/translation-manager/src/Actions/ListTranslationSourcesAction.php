<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Actions;

use Capell\TranslationManager\Contracts\TranslationSourceResolver;
use Capell\TranslationManager\Data\TranslationSourceData;
use Lorisleiva\Actions\Concerns\AsObject;

final class ListTranslationSourcesAction
{
    use AsObject;

    /**
     * @return array<int, TranslationSourceData>
     */
    public function handle(): array
    {
        return resolve(TranslationSourceResolver::class)->sources();
    }
}
