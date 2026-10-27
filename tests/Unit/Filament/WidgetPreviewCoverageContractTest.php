<?php

declare(strict_types=1);

use Capell\FoundationTheme\Enums\FoundationSectionWidgetComponentEnum;
use Capell\LayoutBuilder\Data\LayoutWidgetCatalogDefinitionData;
use Capell\LayoutBuilder\Enums\WidgetComponentEnum;

/**
 * @return list<array{manifest: string, contribution: array<string, mixed>}>
 */
function monorepoManifestContributions(): array
{
    $contributions = [];

    foreach (glob(dirname(__DIR__, 4) . '/*/capell.json') ?: [] as $manifestPath) {
        $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        $entries = is_array($manifest) && is_array($manifest['contributes'] ?? null) ? $manifest['contributes'] : [];

        foreach ($entries as $contribution) {
            if (is_array($contribution)) {
                /** @var array<string, mixed> $contribution */
                $contributions[] = ['manifest' => $manifestPath, 'contribution' => $contribution];
            }
        }
    }

    return $contributions;
}

/**
 * @return array<string, array{file: string, package: string}> Declared picker previews keyed by widget identifier.
 */
function declaredWidgetPreviews(): array
{
    $declared = [];

    foreach (glob(dirname(__DIR__, 4) . '/*/resources/previews/previews.json') ?: [] as $indexPath) {
        $index = json_decode((string) file_get_contents($indexPath), true, flags: JSON_THROW_ON_ERROR);
        $previews = is_array($index) && is_array($index['previews'] ?? null) ? $index['previews'] : [];
        $packageRoot = dirname($indexPath, 3);

        foreach ($previews as $preview) {
            if (is_array($preview) && is_string($preview['widget'] ?? null) && is_string($preview['path'] ?? null)) {
                $declared[$preview['widget']] = ['file' => $packageRoot . '/' . $preview['path'], 'package' => basename($packageRoot)];
            }
        }
    }

    return $declared;
}

/**
 * Every identifier an editor can meet on a picker card: package-registered widgets (manifest
 * content-widget contributions), the Layout Builder catalogue, and the built-in component sets.
 *
 * @return list<string>
 */
function registeredWidgetIdentifiers(): array
{
    $identifiers = [];

    foreach (monorepoManifestContributions() as ['contribution' => $contribution]) {
        $key = $contribution['key'] ?? null;

        if (($contribution['type'] ?? null) === 'content-widget' && is_string($key)) {
            $identifiers[] = $key;
        }
    }

    foreach ([...LayoutWidgetCatalogDefinitionData::defaultCatalog(), ...LayoutWidgetCatalogDefinitionData::extraCatalog()] as $definition) {
        $component = $definition->meta['component'] ?? null;
        $identifiers[] = $component instanceof BackedEnum ? (string) $component->value : (is_string($component) ? $component : $definition->key);
    }

    foreach ([...WidgetComponentEnum::cases(), ...FoundationSectionWidgetComponentEnum::cases()] as $case) {
        $identifiers[] = $case->value;
    }

    $identifiers = array_values(array_unique($identifiers));
    sort($identifiers);

    return $identifiers;
}

it('gives every registered widget a picker preview or lists it as pending capture', function (): void {
    $declared = array_keys(declaredWidgetPreviews());
    $missing = array_values(array_diff(registeredWidgetIdentifiers(), $declared));
    $pending = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/Fixtures/widget-preview-pending.json'), true, flags: JSON_THROW_ON_ERROR);

    // The pending list is a ratchet: it may only shrink as captured previews land.
    expect($missing)->toBe($pending);
});

it('declares only real, correctly sized raster previews for known widgets', function (): void {
    $registered = registeredWidgetIdentifiers();

    foreach (declaredWidgetPreviews() as $widget => $preview) {
        $size = is_file($preview['file']) ? getimagesize($preview['file']) : false;

        expect($registered)->toContain($widget);
        throw_if($size === false, RuntimeException::class, 'Preview is not a readable image: ' . $preview['file']);
        expect($size['mime'])->toBe('image/webp')
            ->and([$size[0], $size[1]])->toBe([480, 300])
            ->and(filesize($preview['file']))->toBeLessThanOrEqual(512 * 1024);
    }
});
