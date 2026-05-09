<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Support;

use Capell\TranslationManager\Contracts\TranslationSourceResolver;
use Capell\TranslationManager\Data\TranslationSourceData;
use InvalidArgumentException;

final class ConfigTranslationSourceResolver implements TranslationSourceResolver
{
    public function sources(): array
    {
        return collect([
            $this->appSource(),
            ...$this->packageSources(),
            ...$this->vendorSources(),
        ])
            ->filter(fn (TranslationSourceData $source): bool => is_dir($source->sourcePath))
            ->unique(fn (TranslationSourceData $source): string => $source->key)
            ->sortBy(fn (TranslationSourceData $source): string => $source->label)
            ->values()
            ->all();
    }

    public function source(string $key): TranslationSourceData
    {
        $source = collect($this->sources())->first(
            fn (TranslationSourceData $candidate): bool => $candidate->key === $key,
        );

        return $source instanceof TranslationSourceData
            ? $source
            : throw new InvalidArgumentException(sprintf('Translation source [%s] is not configured.', $key));
    }

    private function appSource(): TranslationSourceData
    {
        $config = config('capell-translation-manager.app_source', []);
        $path = $config['path'] ?? null;
        $sourcePath = is_string($path) && $path !== '' ? $path : lang_path();

        return new TranslationSourceData(
            key: (string) ($config['key'] ?? 'app'),
            label: (string) ($config['label'] ?? 'Application'),
            sourcePath: $sourcePath,
            overridePath: $sourcePath,
            namespace: null,
            type: 'app',
            sourceWritable: (bool) ($config['writable'] ?? true),
        );
    }

    /**
     * @return array<int, TranslationSourceData>
     */
    private function packageSources(): array
    {
        $sources = [];
        $packagePaths = config('capell-translation-manager.package_paths', []);

        if (! is_array($packagePaths)) {
            return [];
        }

        foreach ($packagePaths as $packagePath) {
            if (! is_string($packagePath) || $packagePath === '') {
                continue;
            }

            foreach (glob($packagePath, GLOB_ONLYDIR) ?: [] as $languagePath) {
                $source = $this->packageSourceFromPath($languagePath);

                if ($source instanceof TranslationSourceData) {
                    $sources[] = $source;
                }
            }
        }

        return $sources;
    }

    private function packageSourceFromPath(string $languagePath): ?TranslationSourceData
    {
        $packagePath = dirname($languagePath, 2);
        $composerPath = $packagePath . '/composer.json';

        if (! is_file($composerPath)) {
            return null;
        }

        $composer = json_decode((string) file_get_contents($composerPath), true);

        if (! is_array($composer) || ! isset($composer['name']) || ! is_string($composer['name'])) {
            return null;
        }

        $packageName = $composer['name'];
        $namespace = str_replace('/', '-', $packageName);
        $label = str($packageName)->after('/')->headline()->toString();

        return new TranslationSourceData(
            key: 'package:' . $packageName,
            label: $label,
            sourcePath: $languagePath,
            overridePath: lang_path('vendor/' . $namespace),
            namespace: $namespace,
            type: 'package',
            sourceWritable: (bool) config('capell-translation-manager.package_source_writes', false),
        );
    }

    /**
     * @return array<int, TranslationSourceData>
     */
    private function vendorSources(): array
    {
        $sources = [];
        $vendors = config('capell-translation-manager.vendor_namespaces', []);

        if (! is_array($vendors)) {
            return [];
        }

        foreach ($vendors as $namespace => $config) {
            if (! is_string($namespace) || ! is_array($config)) {
                continue;
            }

            $path = $config['path'] ?? null;

            if (! is_string($path) || $path === '') {
                continue;
            }

            $sources[] = new TranslationSourceData(
                key: 'vendor:' . $namespace,
                label: (string) ($config['label'] ?? str($namespace)->headline()->toString()),
                sourcePath: $path,
                overridePath: lang_path('vendor/' . $namespace),
                namespace: $namespace,
                type: 'vendor',
                sourceWritable: (bool) ($config['writable'] ?? false),
            );
        }

        return $sources;
    }
}
