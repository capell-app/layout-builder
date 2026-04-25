<?php

declare(strict_types=1);

namespace Capell\DefaultTheme\Support\Tailwind;

use Capell\Core\Contracts\RegistersTailwindAssets;
use Capell\Core\Data\PackageData;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Enums\VendorAssetEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Theme;
use Capell\Core\Support\Tailwind\TailwindAssetsRegistry;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Path;
use Throwable;

/**
 * TailwindAssetsGenerator aggregates Tailwind asset declarations and writes per-theme CSS directive files.
 *
 * Sources of inputs:
 * - Default theme config (capell-default-theme.tailwind) for imports/plugins/sources.
 * - Registered vendor assets for tailwind imports/plugins/sources/theme_colors.
 * - Installed package fallback source globs for resources/views when no explicit source is registered.
 * - Service providers implementing RegistersTailwindAssets for runtime registration.
 * - Each enabled Theme model's meta->colors for dynamic CSS custom properties.
 *
 * Output:
 * - One CSS file per enabled Theme (e.g. resources/css/capell/frontend-default.css).
 * - If a Theme has meta->output_css set, that path is used instead of the derived theme-keyed path.
 * - Falls back to a single base file (no theme key) when no enabled Themes exist in the database.
 *
 * Behavior:
 * - De-duplicates and sorts values via TailwindAssetsRegistry.
 * - Theme colors use last-writer-wins; DB theme colors are registered last and always override package defaults.
 * - Optionally validates @source globs (capell-default-theme.tailwind.validate_sources).
 */
class TailwindAssetsGenerator
{
    public function __construct(private readonly Filesystem $files) {}

    /** Build and return the aggregated registry without writing files, using the default theme for colors. */
    public function collect(): TailwindAssetsRegistry
    {
        $targetPath = $this->targetPath();

        return $this->collectWithTarget($targetPath);
    }

    /**
     * Generate one CSS file per enabled Theme (e.g. frontend-default.css, frontend-dark.css).
     *
     * Falls back to a single base file when no enabled Themes exist in the database.
     *
     * @return array<string>
     */
    public function generate(?string $absoluteBaseTargetPath = null): array
    {
        $baseTargetPath = $this->targetPath($absoluteBaseTargetPath);

        $themes = Theme::query()
            ->with('type')
            ->enabled()
            ->orderByDesc('default')
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        if ($themes->isEmpty()) {
            $this->generateFile($baseTargetPath, null);

            return [$baseTargetPath];
        }

        $generatedPaths = [];
        $seenPaths = [];

        foreach ($themes as $theme) {
            $themePath = $this->themeOutputPath($theme, $baseTargetPath);

            if (array_key_exists($themePath, $seenPaths)) {
                Log::warning('Skipping theme due to output path collision.', [
                    'skipped_theme' => $theme->key,
                    'path' => $themePath,
                    'existing_theme' => $seenPaths[$themePath],
                ]);

                continue;
            }

            $seenPaths[$themePath] = $theme->key;
            $this->generateFile($themePath, $theme);
            $generatedPaths[] = $themePath;
        }

        return $generatedPaths;
    }

    /**
     * Generate the CSS file for a single enabled Theme and return its absolute path.
     *
     * Useful when only one theme's colors have changed and a full regeneration is unnecessary.
     */
    public function generateForTheme(Theme $theme, ?string $absoluteBaseTargetPath = null): string
    {
        if (! $theme->relationLoaded('type')) {
            $theme->load('type');
        }

        $baseTargetPath = $this->targetPath($absoluteBaseTargetPath);
        $themePath = $this->themeOutputPath($theme, $baseTargetPath);

        $this->generateFile($themePath, $theme);

        return $themePath;
    }

    private function generateFile(string $targetPath, ?Theme $theme): void
    {
        $registry = $this->collectWithTarget($targetPath, $theme);

        $content = $this->renderCss($registry);

        $this->files->ensureDirectoryExists(dirname($targetPath));
        $this->files->put($targetPath, $content);

        if ($this->shouldValidateSources()) {
            $this->validateSources($registry, $targetPath);
        }
    }

    private function collectWithTarget(string $targetPath, ?Theme $theme = null): TailwindAssetsRegistry
    {
        $registry = new TailwindAssetsRegistry;

        $this->registerDefaults($registry, $targetPath);
        $this->registerVendorAssets($registry, $targetPath);
        $this->registerInstalledPackageFallbackSources($registry, $targetPath);
        $this->registerProviderAssets($registry);

        if ($theme instanceof Theme) {
            $this->registerThemeColorsFromTheme($registry, $theme);
        } else {
            $this->registerThemeColorsFromDefaultTheme($registry);
        }

        return $registry;
    }

    private function themeOutputPath(Theme $theme, string $baseTargetPath): string
    {
        $metaOutputCss = $theme->getMeta('output_css');

        if (is_string($metaOutputCss) && $metaOutputCss !== '') {
            return Path::isAbsolute($metaOutputCss)
                ? $metaOutputCss
                : $this->resolveAppRelativePath($metaOutputCss);
        }

        $dir = dirname($baseTargetPath);
        $stem = pathinfo($baseTargetPath, PATHINFO_FILENAME);
        $extension = pathinfo($baseTargetPath, PATHINFO_EXTENSION);

        return $dir . '/' . $stem . '-' . $theme->key . ($extension !== '' ? '.' . $extension : '');
    }

    private function registerDefaults(TailwindAssetsRegistry $registry, string $targetPath): void
    {
        $config = config('capell-default-theme.tailwind', []);
        $origin = 'config:capell-default-theme.tailwind';

        $registry
            ->registerImports(($config['imports'] ?? []), $origin)
            ->registerPlugins(($config['plugins'] ?? []), $origin);

        foreach (($config['sources'] ?? []) as $source) {
            if (! is_string($source)) {
                continue;
            }

            if ($source === '') {
                continue;
            }

            if (Path::isAbsolute($source)) {
                $resolved = $source;
            } else {
                // Treat 'resources/*' as application resources, not package resources
                $resolved = $this->resolveAppRelativePath($source);
            }

            $registry->registerSource($this->relativePath($resolved, $targetPath), $origin);
        }
    }

    private function registerVendorAssets(TailwindAssetsRegistry $registry, string $targetPath): void
    {
        $this->installedVendorAssetsForType(VendorAssetEnum::TailwindImport)
            ->each(function (VendorAssetData $asset) use ($registry, $targetPath): void {
                $import = trim($asset->value);

                if ($import === '') {
                    return;
                }

                if ($this->isNodeModuleImport($import)) {
                    $registry->registerImport($import, $this->originForAsset($asset));

                    return;
                }

                $resolved = Path::isAbsolute($import) ? $import : $this->resolveAssetPath($asset, $import);

                $registry->registerImport($this->relativePath($resolved, $targetPath), $this->originForAsset($asset));
            });

        $this->installedVendorAssetsForType(VendorAssetEnum::TailwindPlugin)
            ->each(function (VendorAssetData $asset) use ($registry): void {
                $registry->registerPlugin($asset->value, $this->originForAsset($asset));
            });

        $this->installedVendorAssetsForType(VendorAssetEnum::TailwindSource)
            ->each(function (VendorAssetData $asset) use ($registry, $targetPath): void {
                $source = trim($asset->value);

                if ($source === '') {
                    return;
                }

                $resolved = Path::isAbsolute($source) ? $source : $this->resolveAssetPath($asset, $source);

                $registry->registerSource($this->relativePath($resolved, $targetPath), $this->originForAsset($asset));
            });

        $this->installedVendorAssetsForType(VendorAssetEnum::TailwindThemeColor)
            ->each(function (VendorAssetData $asset) use ($registry): void {
                $colorName = trim($asset->value);
                $colorValue = $asset->secondaryValue !== null ? trim($asset->secondaryValue) : '';

                if ($colorName === '' || $colorValue === '') {
                    return;
                }

                $registry->registerThemeColor($colorName, $colorValue, $this->originForAsset($asset));
            });
    }

    private function registerInstalledPackageFallbackSources(TailwindAssetsRegistry $registry, string $targetPath): void
    {
        $packagesWithExplicitSources = CapellCore::getVendorAssetsForType(VendorAssetEnum::TailwindSource)
            ->pluck('packageName')
            ->filter(fn (mixed $name): bool => is_string($name) && $name !== '')
            ->unique()
            ->all();

        CapellCore::getInstalledPackages()
            ->reject(fn (PackageData $package): bool => in_array($package->name, $packagesWithExplicitSources, true))
            ->each(function (PackageData $package) use ($registry, $targetPath): void {
                $fallback = $this->resolveVendorPackageAbsolute($package->name, 'resources/views/**/*.blade.php');

                $registry->registerSource($this->relativePath($fallback, $targetPath), 'package:' . $package->name);
            });
    }

    private function registerProviderAssets(TailwindAssetsRegistry $registry): void
    {
        /** @var array<int, object> $providers */
        $providers = app()->getProviders(ServiceProvider::class);

        foreach ($providers as $provider) {
            if (! $provider instanceof RegistersTailwindAssets) {
                continue;
            }

            try {
                $provider->registerTailwindAssets($registry);
            } catch (Throwable $exception) {
                Log::warning('Failed to register Tailwind assets from provider.', [
                    'provider' => $provider::class,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    private function renderCss(TailwindAssetsRegistry $registry): string
    {
        $lines = collect();

        $lines = $lines->merge($registry->imports()->map(fn (string $import): string => sprintf('@import "%s";', $import)));

        if ($registry->hasThemeColors()) {
            $lines->push($this->renderThemeBlock($registry->themeColors()));
        }

        $lines = $lines->merge($registry->plugins()->map(fn (string $plugin): string => sprintf('@plugin "%s";', $plugin)));
        $lines = $lines->merge($registry->sources()->map(fn (string $source): string => sprintf('@source "%s";', $source)));

        return $lines->implode(PHP_EOL) . PHP_EOL;
    }

    /** @param Collection<string, string> $colors */
    private function renderThemeBlock(Collection $colors): string
    {
        $inner = $colors
            ->map(fn (string $value, string $name): string => sprintf('  --color-%s: %s;', $name, $value))
            ->values()
            ->implode(PHP_EOL);

        return '@theme {' . PHP_EOL . $inner . PHP_EOL . '}';
    }

    private function registerThemeColorsFromTheme(TailwindAssetsRegistry $registry, Theme $theme): void
    {
        $colors = [];

        foreach ($theme->colors as $name => $value) {
            if (! is_string($name)) {
                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            if ($value === '') {
                continue;
            }

            $colors[$name] = $value;
        }

        if ($colors === []) {
            return;
        }

        $registry->registerThemeColors($colors, 'theme:' . $theme->key);
    }

    private function registerThemeColorsFromDefaultTheme(TailwindAssetsRegistry $registry): void
    {
        $theme = Theme::query()->where('default', true)->first();

        if ($theme === null) {
            return;
        }

        $this->registerThemeColorsFromTheme($registry, $theme);
    }

    private function isNodeModuleImport(string $import): bool
    {
        $import = ltrim($import);

        if (str_starts_with($import, 'resources/')) {
            return false;
        }

        if (str_starts_with($import, './') || str_starts_with($import, '../') || str_starts_with($import, '/')) {
            return false;
        }

        // Scoped or bare package names (e.g., @scope/pkg, tippy.js, tailwindcss/base)
        if (str_starts_with($import, '@')) {
            return true;
        }

        // Known packages
        if (str_starts_with($import, 'tippy.js') || str_starts_with($import, 'tailwindcss')) {
            return true;
        }

        // Heuristic: no leading dot or slash, first segment contains only package chars
        return preg_match('~^[a-z0-9_.-]+(?:/.*)?$~i', $import) === 1;
    }

    private function targetPath(?string $overrideAbsolutePath = null): string
    {
        if (is_string($overrideAbsolutePath) && $overrideAbsolutePath !== '') {
            return $overrideAbsolutePath;
        }

        $configPath = config('capell-default-theme.tailwind.output_css');

        throw_if(! is_string($configPath) || $configPath === '', InvalidArgumentException::class, 'Tailwind output CSS path is not configured');

        if (Path::isAbsolute($configPath)) {
            return $configPath;
        }

        // Relative to application base (resource_path preferable for resources/*)
        if (str_starts_with($configPath, 'resources/')) {
            return rtrim(resource_path(''), '/') . '/' . substr($configPath, strlen('resources/'));
        }

        return rtrim(base_path(''), '/') . '/' . ltrim($configPath, '/');
    }

    private function relativePath(string $path, string $targetPath): string
    {
        $targetDir = dirname($targetPath);

        $path = str_replace('\\', '/', $path);
        $targetDir = str_replace('\\', '/', $targetDir);

        return Path::makeRelative($path, $targetDir);
    }

    private function shouldValidateSources(): bool
    {
        return config('capell-default-theme.tailwind.validate_sources', false);
    }

    private function validateSources(TailwindAssetsRegistry $registry, string $targetPath): void
    {
        $targetDir = dirname($targetPath);

        foreach ($registry->sources() as $source) {
            $absolute = Path::isAbsolute($source)
                ? $source
                : Path::join($targetDir, $source);

            try {
                $matches = glob($absolute, GLOB_BRACE);
            } catch (Throwable $exception) {
                Log::warning('Failed to validate Tailwind source glob.', [
                    'source' => $source,
                    'path' => $absolute,
                    'error' => $exception->getMessage(),
                ]);

                continue;
            }

            if (! $matches) {
                Log::warning('Tailwind source glob did not match any files.', [
                    'source' => $source,
                    'path' => $absolute,
                ]);
            }
        }
    }

    private function resolveAppRelativePath(string $path): string
    {
        if (Path::isAbsolute($path)) {
            return $path;
        }

        if (str_starts_with($path, 'resources/')) {
            return rtrim(resource_path(''), '/') . '/' . substr($path, strlen('resources/'));
        }

        // Default to base_path for other relative entries
        return rtrim(base_path(''), '/') . '/' . ltrim($path, '/');
    }

    private function resolveVendorPackageAbsolute(string $packageName, string $relativePackagePath): string
    {
        $inner = ltrim($relativePackagePath, '/');

        // Build absolute path pointing into application vendor directory using composer package name
        $absolute = rtrim(base_path(''), '/') . '/vendor/' . $packageName . '/' . $inner;

        return $absolute;
    }

    /** @return Collection<int, VendorAssetData> */
    private function installedVendorAssetsForType(VendorAssetEnum $type): Collection
    {
        return CapellCore::getVendorAssetsForType($type)
            ->filter(fn (VendorAssetData $asset): bool => $asset->packageName === null || CapellCore::isPackageInstalled($asset->packageName))
            ->values();
    }

    private function originForAsset(VendorAssetData $asset): string
    {
        return $asset->packageName === null ? 'vendor-asset:global' : 'vendor-asset:' . $asset->packageName;
    }

    private function resolveAssetPath(VendorAssetData $asset, string $relativePath): string
    {
        if ($asset->packageName !== null && $asset->packageName !== '') {
            return $this->resolveVendorPackageAbsolute($asset->packageName, $relativePath);
        }

        return $this->resolveAppRelativePath($relativePath);
    }
}
