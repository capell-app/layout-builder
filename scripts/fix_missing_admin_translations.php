<?php

declare(strict_types=1);

/**
 * Finds translation usages starting with __('capell-admin:: or trans('capell-admin:: inside packages/.
 * Loads available keys from vendor/capell-app/admin/packages/admin/resources/lang/en.
 * Replaces missing keys with capell-layout:: namespace when --write flag is supplied.
 * Dry-run by default: lists missing keys and affected files.
 *
 * Usage:
 *   php scripts/fix_missing_admin_translations.php          # dry run
 *   php scripts/fix_missing_admin_translations.php --write  # perform replacements
 */

$projectRoot = dirname(__DIR__);
$packagesPath = $projectRoot . '/packages';
$adminLangPath = $projectRoot . '/vendor/capell-app/admin/packages/admin/resources/lang/en';
$doWrite = in_array('--write', $argv, true);
$debug = in_array('--debug', $argv, true);

if (! is_dir($packagesPath)) {
    fwrite(STDERR, "Packages directory not found: $packagesPath\n");
    exit(1);
}

$usedKeys = collectUsedTranslationKeys($packagesPath);
$availableKeys = collectAvailableAdminLanguageKeys($adminLangPath);

// Filter out capell-admin::generic. (with nothing after the dot)
$usedKeys = array_filter($usedKeys, function ($key) {
    if (preg_match('/^capell-admin::generic\.$/', $key)) {
        fwrite(STDERR, "Warning: Found usage of capell-admin::generic. (empty key) in code. This is not a valid translation key.\n");
        return false;
    }
    return true;
});
$usedKeys = array_values($usedKeys);

$missingKeys = array_diff($usedKeys, $availableKeys);

if ($debug) {
    echo "\n--- Used keys ---\n";
    foreach ($usedKeys as $k) echo $k . "\n";
    echo "\n--- Available keys ---\n";
    foreach ($availableKeys as $k) echo $k . "\n";
    echo "\n--- Missing keys ---\n";
    foreach ($missingKeys as $k) echo $k . "\n";
}

if (empty($missingKeys)) {
    echo "No missing capell-admin:: translation keys found.\n";
    exit(0);
}

$filesModified = [];
$replacedKeys = [];

foreach (scanPhpFiles($packagesPath) as $file) {
    $contents = file_get_contents($file);
    $original = $contents;
    foreach ($missingKeys as $fullKey) {
        if (str_contains($contents, $fullKey)) {
            $layoutKey = preg_replace('/^capell-admin::/', 'capell-layout::', $fullKey);
            $contents = str_replace($fullKey, $layoutKey, $contents);
            $replacedKeys[$fullKey] = $layoutKey;
        }
    }
    if ($contents !== $original) {
        if ($doWrite) {
            file_put_contents($file, $contents);
        }
        $filesModified[$file] = true;
    }
}

if (! $doWrite) {
    echo "Missing keys (would replace capell-admin:: with capell-layout::):\n";
    foreach ($missingKeys as $key) {
        echo $key . "\n";
    }
    echo "\nAffected files (dry-run):\n";
    foreach (array_keys($filesModified) as $file) {
        echo $file . "\n";
    }
    echo "\nRun with --write to apply replacements.\n";
    exit(0);
}

if (!empty($replacedKeys)) {
    echo "Replaced translation keys:\n";
    foreach ($replacedKeys as $old => $new) {
        echo "$new\n";
    }
    echo "\n";
}

echo "Replaced namespace for " . count($missingKeys) . " missing keys in " . count($filesModified) . " file(s).\n";
exit(0);

/** @return array<int,string> */
function collectUsedTranslationKeys(string $root): array
{
    $keys = [];
    foreach (scanPhpFiles($root) as $file) {
        $contents = file_get_contents($file);
        preg_match_all('/__\(\s*[\"\'](capell-admin::[A-Za-z0-9_.-]+)[\"\']/', $contents, $matches1);
        preg_match_all('/trans\(\s*[\"\'](capell-admin::[A-Za-z0-9_.-]+)[\"\']/', $contents, $matches2);
        // Also match capell-admin::generic.*
        preg_match_all('/capell-admin::generic\.[A-Za-z0-9_.-]+/', $contents, $matches3);
        foreach (array_merge($matches1[1], $matches2[1], $matches3[0]) as $key) {
            $keys[$key] = true;
        }
    }
    return array_keys($keys);
}

/** @return array<int,string> */
function collectAvailableAdminLanguageKeys(string $langPath): array
{
    $keys = [];
    if (! is_dir($langPath)) {
        return [];
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($langPath));
    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $group = basename($file->getFilename(), '.php');
        $arr = include $file->getPathname();
        if (! is_array($arr)) {
            continue;
        }
        $flat = flattenArray($arr);
        foreach ($flat as $dotKey => $_) {
            $keys["capell-admin::{$group}.{$dotKey}"] = true;
        }
    }
    return array_keys($keys);
}

/** @param array<mixed> $array */
function flattenArray(array $array, string $prefix = ''): array
{
    $result = [];
    foreach ($array as $key => $value) {
        $fullKey = $prefix === '' ? (string) $key : $prefix . '.' . (string) $key;
        if (is_array($value)) {
            $result += flattenArray($value, $fullKey);
        } else {
            $result[$fullKey] = $value;
        }
    }
    return $result;
}

/** @return Generator<int,string> */
function scanPhpFiles(string $root): Generator
{
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }
        $ext = $file->getExtension();
        if ($ext !== 'php') {
            continue;
        }
        yield $file->getPathname();
    }
}
