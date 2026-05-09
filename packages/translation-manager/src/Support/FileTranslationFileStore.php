<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Support;

use Capell\TranslationManager\Contracts\TranslationFileStore;
use Capell\TranslationManager\Data\LocaleSummaryData;
use Capell\TranslationManager\Data\TranslationEntryData;
use Capell\TranslationManager\Data\TranslationFileData;
use Capell\TranslationManager\Data\TranslationSourceData;
use Capell\TranslationManager\Data\TranslationWriteData;
use Illuminate\Filesystem\Filesystem;

final class FileTranslationFileStore implements TranslationFileStore
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly LocaleValidator $localeValidator,
    ) {}

    public function locales(TranslationSourceData $source): array
    {
        $locales = [];

        foreach ([$source->sourcePath, $source->overridePath] as $path) {
            if (! $this->filesystem->isDirectory($path)) {
                continue;
            }

            foreach ($this->filesystem->directories($path) as $localePath) {
                $locale = basename($localePath);
                $locales[$locale] ??= [
                    'locale' => $locale,
                    'source' => false,
                    'override' => false,
                ];

                if (str_starts_with($localePath, $source->sourcePath)) {
                    $locales[$locale]['source'] = true;
                }

                if (str_starts_with($localePath, $source->overridePath)) {
                    $locales[$locale]['override'] = true;
                }
            }

            foreach (glob($path . '/*.json') ?: [] as $jsonPath) {
                $locale = basename($jsonPath, '.json');
                $locales[$locale] ??= [
                    'locale' => $locale,
                    'source' => false,
                    'override' => false,
                ];

                if (str_starts_with($jsonPath, $source->sourcePath)) {
                    $locales[$locale]['source'] = true;
                }

                if (str_starts_with($jsonPath, $source->overridePath)) {
                    $locales[$locale]['override'] = true;
                }
            }
        }

        return collect($locales)
            ->map(fn (array $locale): LocaleSummaryData => new LocaleSummaryData(
                locale: (string) $locale['locale'],
                fileCount: count($this->files($source, (string) $locale['locale'], (string) $locale['locale'])),
                sourceAvailable: (bool) $locale['source'],
                overrideAvailable: (bool) $locale['override'],
            ))
            ->sortBy(fn (LocaleSummaryData $locale): string => $locale->locale)
            ->values()
            ->all();
    }

    public function files(TranslationSourceData $source, string $sourceLocale, string $targetLocale): array
    {
        $files = [];

        foreach ([$sourceLocale, $targetLocale] as $locale) {
            foreach ([$source->sourcePath, $source->overridePath] as $basePath) {
                $localePath = $basePath . '/' . $locale;

                if (! $this->filesystem->isDirectory($localePath)) {
                    continue;
                }

                foreach ($this->filesystem->allFiles($localePath) as $file) {
                    if ($file->getExtension() !== 'php') {
                        continue;
                    }

                    $relativePath = str_replace('\\', '/', $file->getRelativePathname());
                    $name = substr($relativePath, 0, -4);
                    $files['php:' . $name] = new TranslationFileData(
                        key: 'php:' . $name,
                        label: $relativePath,
                        type: 'php',
                        relativePath: $relativePath,
                    );
                }
            }

            foreach ([$source->sourcePath, $source->overridePath] as $basePath) {
                if ($this->filesystem->exists($basePath . '/' . $locale . '.json')) {
                    $files['json'] = new TranslationFileData(
                        key: 'json',
                        label: 'JSON translations',
                        type: 'json',
                        relativePath: $locale . '.json',
                    );
                }
            }
        }

        return collect($files)
            ->sortBy(fn (TranslationFileData $file): string => $file->label)
            ->values()
            ->all();
    }

    public function comparison(TranslationSourceData $source, string $fileKey, string $sourceLocale, string $targetLocale): array
    {
        $sourceEntries = TranslationArray::flattenForEditor($this->read($source, $fileKey, $sourceLocale, false));
        $targetEntries = TranslationArray::flattenForEditor($this->read($source, $fileKey, $targetLocale, false));
        $keys = collect([...array_keys($sourceEntries), ...array_keys($targetEntries)])->unique()->sort()->values();

        return $keys
            ->map(function (string $key) use ($sourceEntries, $targetEntries): TranslationEntryData {
                $sourceEntry = $sourceEntries[$key] ?? ['value' => null, 'editable' => false, 'exists' => false];
                $targetEntry = $targetEntries[$key] ?? ['value' => null, 'editable' => true, 'exists' => false];
                $sourceValue = $sourceEntry['value'];
                $targetValue = $targetEntry['value'];
                $sourceExists = (bool) $sourceEntry['exists'];
                $targetExists = (bool) $targetEntry['exists'];

                return new TranslationEntryData(
                    key: $key,
                    sourceValue: is_string($sourceValue) ? $sourceValue : null,
                    targetValue: is_string($targetValue) ? $targetValue : null,
                    status: $this->status($sourceExists, $targetExists, is_string($sourceValue) ? $sourceValue : null, is_string($targetValue) ? $targetValue : null),
                    editable: (bool) $sourceEntry['editable'] || (bool) $targetEntry['editable'],
                );
            })
            ->all();
    }

    public function createLocale(TranslationSourceData $source, string $locale, string $sourceLocale): void
    {
        $this->localeValidator->assertValid($locale);

        foreach ($this->files($source, $sourceLocale, $sourceLocale) as $file) {
            $sourceValues = TranslationArray::flattenStrings($this->read($source, $file->key, $sourceLocale, false));
            $blankValues = array_fill_keys(array_keys($sourceValues), '');

            $this->write(new TranslationWriteData(
                source: $source,
                fileKey: $file->key,
                locale: $locale,
                values: $blankValues,
            ));
        }
    }

    public function duplicateLocale(TranslationSourceData $source, string $fromLocale, string $targetLocale): void
    {
        $this->localeValidator->assertValid($fromLocale);
        $this->localeValidator->assertValid($targetLocale);

        foreach ($this->files($source, $fromLocale, $fromLocale) as $file) {
            $values = TranslationArray::flattenStrings($this->read($source, $file->key, $fromLocale, false));

            $this->write(new TranslationWriteData(
                source: $source,
                fileKey: $file->key,
                locale: $targetLocale,
                values: $values,
            ));
        }
    }

    public function write(TranslationWriteData $write): void
    {
        $this->localeValidator->assertValid($write->locale);

        $currentValues = $this->read($write->source, $write->fileKey, $write->locale, true);

        foreach ($write->values as $key => $value) {
            $currentValues = TranslationArray::setNestedValue($currentValues, $key, $value ?? '');
        }

        $this->writeValues($write->source, $write->fileKey, $write->locale, $currentValues);
    }

    /**
     * @return array<string, mixed>
     */
    private function read(TranslationSourceData $source, string $fileKey, string $locale, bool $forWrite): array
    {
        $path = $this->path($source, $fileKey, $locale, $forWrite);

        if ($fileKey === 'json') {
            if (! $this->filesystem->exists($path)) {
                return [];
            }

            $decoded = json_decode((string) $this->filesystem->get($path), true);

            return is_array($decoded) ? $decoded : [];
        }

        if (! $this->filesystem->exists($path)) {
            return [];
        }

        $values = require $path;

        return is_array($values) ? $values : [];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function writeValues(TranslationSourceData $source, string $fileKey, string $locale, array $values): void
    {
        $path = $this->path($source, $fileKey, $locale, true);
        $this->filesystem->ensureDirectoryExists(dirname($path));

        if ($fileKey === 'json') {
            $encoded = json_encode($values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->filesystem->put($path, ($encoded === false ? '{}' : $encoded) . PHP_EOL);

            return;
        }

        $this->filesystem->put($path, $this->exportPhpArray($values));
    }

    private function path(TranslationSourceData $source, string $fileKey, string $locale, bool $forWrite): string
    {
        if ($fileKey === 'json') {
            return $this->basePath($source, $fileKey, $locale, $forWrite) . '/' . $locale . '.json';
        }

        $name = str($fileKey)->after('php:')->toString();

        return $this->basePath($source, $fileKey, $locale, $forWrite) . '/' . $locale . '/' . $name . '.php';
    }

    private function basePath(TranslationSourceData $source, string $fileKey, string $locale, bool $forWrite): string
    {
        if (! $forWrite) {
            $overridePath = $this->rawPath($source->overridePath, $fileKey, $locale);

            return $this->filesystem->exists($overridePath) ? $source->overridePath : $source->sourcePath;
        }

        if ($source->type === 'app') {
            return $source->sourcePath;
        }

        $overridePath = $this->rawPath($source->overridePath, $fileKey, $locale);

        if ($this->filesystem->exists($overridePath) || ! $source->sourceWritable) {
            return $source->overridePath;
        }

        return $source->sourcePath;
    }

    private function rawPath(string $basePath, string $fileKey, string $locale): string
    {
        if ($fileKey === 'json') {
            return $basePath . '/' . $locale . '.json';
        }

        return $basePath . '/' . $locale . '/' . str($fileKey)->after('php:')->toString() . '.php';
    }

    private function status(bool $sourceExists, bool $targetExists, ?string $sourceValue, ?string $targetValue): string
    {
        if (! $sourceExists && $targetExists) {
            return 'extra';
        }

        if (! $targetExists || $targetValue === null || $targetValue === '') {
            return 'missing';
        }

        if ($sourceValue === $targetValue) {
            return 'same';
        }

        return 'changed';
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function exportPhpArray(array $values): string
    {
        $export = var_export($values, true);
        $export = str_replace('array (', '[', $export);
        $export = str_replace(')', ']', $export);
        $export = preg_replace('/^([ ]*)/m', '$1', $export) ?? $export;

        return "<?php\n\ndeclare(strict_types=1);\n\nreturn " . $export . ";\n";
    }
}
