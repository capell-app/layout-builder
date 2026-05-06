<?php

declare(strict_types=1);

namespace Capell\Tests\Support\Concerns;

trait RegistersPublishedConfigs
{
    protected function getDefaultPackages(): array
    {
        return [
            'filament-shield' => [
                'user' => 'bezhansalleh',
                'name' => 'filament-shield',
                'file' => 'filament-shield',
            ],
            'login-audit' => [
                'user' => 'rappasoft',
                'name' => 'laravel-login-audit',
                'file' => 'login-audit',
            ],
            'permission' => [
                'user' => 'spatie',
                'name' => 'laravel-permission',
                'file' => 'permission',
            ],
            'settings' => [
                'user' => 'spatie',
                'name' => 'laravel-settings',
                'file' => 'settings',
            ],
        ];
    }

    protected function registerPublishConfig(string $package, bool $vendorPackage = false): void
    {
        $configs = $this->getPublishConfigs($package, $vendorPackage);

        foreach ($configs as $configFile) {
            /** @var string $configFile */
            $config = require $configFile;
            $configName = basename($configFile, '.php');

            $this->registerPackageConfig($configName, $config);
        }
    }

    /**
     * @return array<int, string>
     */
    protected function getPublishConfigs(string $package, bool $vendorPackage): array
    {
        if ($vendorPackage) {
            $path = realpath(__DIR__ . '/../../../vendor/capell-app/' . $package . '/publishes/config');
        } else {
            $path = realpath(__DIR__ . '/../../../packages/' . $package . '/publishes/config');
        }

        if (in_array($path, ['', '0', false], true)) {
            return [];
        }

        $configPaths = glob($path . '/*.php');

        if ($configPaths === false) {
            return [];
        }

        return $configPaths;
    }

    private function getPackageFile(array $package): string
    {
        $path = '/../vendor/' . basename((string) $package['user']) . '/' . basename((string) $package['name']) . '/config';
        $file = basename((string) $package['file']) . '.php';

        return sprintf('%s/%s', $path, $file);
    }

    private function registerPackageConfig(string $package, array $config): void
    {
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $this->registerPackageConfig(sprintf('%s.%s', $package, $key), $value);

                continue;
            }

            config()->set(sprintf('%s.%s', $package, $key), $value);
        }
    }
}
