<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Contracts;

use Capell\TranslationManager\Data\LocaleSummaryData;
use Capell\TranslationManager\Data\TranslationEntryData;
use Capell\TranslationManager\Data\TranslationFileData;
use Capell\TranslationManager\Data\TranslationSourceData;
use Capell\TranslationManager\Data\TranslationWriteData;

interface TranslationFileStore
{
    /**
     * @return array<int, LocaleSummaryData>
     */
    public function locales(TranslationSourceData $source): array;

    /**
     * @return array<int, TranslationFileData>
     */
    public function files(TranslationSourceData $source, string $sourceLocale, string $targetLocale): array;

    /**
     * @return array<int, TranslationEntryData>
     */
    public function comparison(TranslationSourceData $source, string $fileKey, string $sourceLocale, string $targetLocale): array;

    public function createLocale(TranslationSourceData $source, string $locale, string $sourceLocale): void;

    public function duplicateLocale(TranslationSourceData $source, string $fromLocale, string $targetLocale): void;

    public function write(TranslationWriteData $write): void;
}
