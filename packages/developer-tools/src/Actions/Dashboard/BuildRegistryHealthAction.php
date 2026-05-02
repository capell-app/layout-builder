<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Actions\Dashboard;

use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Data\PageTypeData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\PackageRegistry\CapellPackageRegistry;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\DeveloperTools\Data\Dashboard\RegistryEntryData;
use Capell\DeveloperTools\Data\Dashboard\RegistryHealthData;
use Capell\DeveloperTools\Data\Dashboard\RegistrySectionData;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\LaravelData\DataCollection;

final class BuildRegistryHealthAction
{
    use AsAction;

    public function handle(): RegistryHealthData
    {
        $sections = [
            $this->buildPageTypesSection(),
            $this->buildConfiguratorsSection(),
            $this->buildSchemaExtendersSection(),
            $this->buildSettingsSchemasSection(),
        ];

        return new RegistryHealthData(
            sections: RegistrySectionData::collect($sections, DataCollection::class),
        );
    }

    private function buildPageTypesSection(): RegistrySectionData
    {
        $entries = CapellCore::getPageTypes()
            ->map(function (PageTypeData $type): RegistryEntryData {
                $class = $type->model;

                return new RegistryEntryData(
                    class: $class,
                    sourcePackage: $this->sourcePackageOf($class),
                    autoDiscovered: $this->isAutoDiscovered($class),
                );
            })
            ->values()
            ->all();

        return new RegistrySectionData(
            name: 'Page types',
            count: count($entries),
            entries: RegistryEntryData::collect($entries, DataCollection::class),
        );
    }

    private function buildConfiguratorsSection(): RegistrySectionData
    {
        $allConfigurators = CapellAdmin::getConfigurators();

        $entries = [];
        foreach ($allConfigurators as $configuratorClasses) {
            foreach ($configuratorClasses as $class) {
                $entries[] = new RegistryEntryData(
                    class: $class,
                    sourcePackage: $this->sourcePackageOf($class),
                    autoDiscovered: $this->isAutoDiscovered($class),
                );
            }
        }

        return new RegistrySectionData(
            name: 'Configurators',
            count: count($entries),
            entries: RegistryEntryData::collect($entries, DataCollection::class),
        );
    }

    private function buildSchemaExtendersSection(): RegistrySectionData
    {
        $tagged = app()->tagged(PageSchemaExtender::TAG);

        $entries = [];
        foreach ($tagged as $extender) {
            $class = is_object($extender) ? $extender::class : (string) $extender;
            $entries[] = new RegistryEntryData(
                class: $class,
                sourcePackage: $this->sourcePackageOf($class),
                autoDiscovered: $this->isAutoDiscovered($class),
            );
        }

        return new RegistrySectionData(
            name: 'Page schema extenders',
            count: count($entries),
            entries: RegistryEntryData::collect($entries, DataCollection::class),
        );
    }

    private function buildSettingsSchemasSection(): RegistrySectionData
    {
        $registry = resolve(SettingsSchemaRegistry::class);

        $entries = [];
        foreach ($registry->all() as $schemaClasses) {
            foreach ($schemaClasses as $class) {
                $entries[] = new RegistryEntryData(
                    class: $class,
                    sourcePackage: $this->sourcePackageOf($class),
                    autoDiscovered: $this->isAutoDiscovered($class),
                );
            }
        }

        return new RegistrySectionData(
            name: 'Settings schemas',
            count: count($entries),
            entries: RegistryEntryData::collect($entries, DataCollection::class),
        );
    }

    private function sourcePackageOf(string $class): string
    {
        $map = resolve(CapellPackageRegistry::class)->namespaceMap();
        $map['App\\'] = 'host-app';

        foreach ($map as $prefix => $shortName) {
            if (str_starts_with($class, $prefix)) {
                return $shortName;
            }
        }

        return 'unknown';
    }

    private function isAutoDiscovered(string $class): bool
    {
        return str_starts_with($class, 'App\\');
    }
}
