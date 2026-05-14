<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Actions\Dashboard;

use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Core\Data\PageTypeData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\PackageRegistry\CapellPackageRegistry;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\Diagnostics\Data\Dashboard\RegistryEntryData;
use Capell\Diagnostics\Data\Dashboard\RegistryHealthData;
use Capell\Diagnostics\Data\Dashboard\RegistrySectionData;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\LaravelData\DataCollection;

final class BuildRegistryHealthAction
{
    use AsAction;

    private const BLOCK_REGISTRY_CLASS = 'Capell\\ContentBlocks\\Support\\BlockRegistry';

    private const MAX_REGISTRY_ENTRIES_PER_SECTION = 100;

    public function handle(): RegistryHealthData
    {
        $sections = [
            $this->buildPageTypesSection(),
            $this->buildConfiguratorsSection(),
            $this->buildSchemaExtendersSection(),
            $this->buildSettingsSchemasSection(),
        ];

        $contentBlocksSection = $this->buildContentBlocksSection();

        if ($contentBlocksSection instanceof RegistrySectionData) {
            $sections[] = $contentBlocksSection;
        }

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
        $allConfigurators = AdminSurfaceLookup::configuratorGroups();

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

    private function buildContentBlocksSection(): ?RegistrySectionData
    {
        if (! class_exists(self::BLOCK_REGISTRY_CLASS) || ! app()->bound(self::BLOCK_REGISTRY_CLASS)) {
            return null;
        }

        $registry = app(self::BLOCK_REGISTRY_CLASS);

        if (! method_exists($registry, 'all')) {
            return null;
        }

        $entries = [];

        $definitions = $registry->all();

        $definitionCount = count($definitions);

        foreach (array_slice($definitions, 0, self::MAX_REGISTRY_ENTRIES_PER_SECTION) as $definition) {
            $key = property_exists($definition, 'key') ? $definition->key : 'unknown';
            $sourcePackage = property_exists($definition, 'sourcePackage') ? $definition->sourcePackage : 'unknown';

            $entries[] = new RegistryEntryData(
                class: 'block:' . $key,
                sourcePackage: $sourcePackage,
                autoDiscovered: false,
            );
        }

        return new RegistrySectionData(
            name: 'Content blocks',
            count: $definitionCount,
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
