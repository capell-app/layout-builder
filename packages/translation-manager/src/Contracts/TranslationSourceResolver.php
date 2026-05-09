<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Contracts;

use Capell\TranslationManager\Data\TranslationSourceData;

interface TranslationSourceResolver
{
    /**
     * @return array<int, TranslationSourceData>
     */
    public function sources(): array;

    public function source(string $key): TranslationSourceData;
}
