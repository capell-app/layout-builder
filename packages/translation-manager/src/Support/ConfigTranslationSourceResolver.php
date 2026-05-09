<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Support;

use Capell\TranslationManager\Contracts\TranslationSourceResolver;
use Capell\TranslationManager\Data\TranslationSourceData;
use InvalidArgumentException;
use ReflectionClass;

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
        $key = $config['key'] ?? 'app';
        $label = $config['label'] ?? 'Application';
        $writable = $config['writable'] ?? true;

        return new TranslationSourceData(
            key: is_string($key) ? $key : 'app',
            label: is_string($label) ? $label : 'Application',
            sourcePath: $sourcePath,
            overridePath: $sourcePath,
            namespace: null,
            type: 'app',
            sourceWritable: is_bool($writable) ? $writable : true,
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
            if (! is_string($packagePath)) {
                continue;
            }

            if ($packagePath === '') {
                continue;
            }

            $languagePaths = glob($packagePath, GLOB_ONLYDIR);

            foreach (is_array($languagePaths) ? $languagePaths : [] as $languagePath) {
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
        $namespace = $this->packageTranslationNamespace($composer, $packageName);
        $label = str($packageName)->after('/')->headline()->toString();

        return new TranslationSourceData(
            key: 'package:' . $packageName,
            label: $label,
            sourcePath: $languagePath,
            overridePath: lang_path('vendor/' . $namespace),
            namespace: $namespace,
            type: 'package',
            sourceWritable: config('capell-translation-manager.package_source_writes', false) === true,
        );
    }

    /**
     * @param  array<string, mixed>  $composer
     */
    private function packageTranslationNamespace(array $composer, string $packageName): string
    {
        $providers = $composer['extra']['laravel']['providers'] ?? [];
        $provider = is_array($providers) ? collect($providers)->first(fn (mixed $candidate): bool => is_string($candidate)) : null;

        if (! is_string($provider) || ! class_exists($provider)) {
            return str_replace('/', '-', $packageName);
        }

        $reflection = new ReflectionClass($provider);

        if (! $reflection->hasProperty('name')) {
            return str_replace('/', '-', $packageName);
        }

        $property = $reflection->getProperty('name');

        if (! $property->isStatic() || ! $property->isPublic()) {
            return str_replace('/', '-', $packageName);
        }

        $value = $property->getValue();

        return is_string($value) && $value !== ''
            ? $value
            : str_replace('/', '-', $packageName);
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
            if (! is_string($namespace)) {
                continue;
            }

            if (! is_array($config)) {
                continue;
            }

            $path = $config['path'] ?? null;
            if (! is_string($path)) {
                continue;
            }

            if ($path === '') {
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
